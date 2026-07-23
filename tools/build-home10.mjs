/* =========================================================================
   build-home10.mjs -- prepare the Home 10 plates and transitions for the web.

     node tools/build-home10.mjs <staging-dir>

   <staging-dir> holds the raw Higgsfield downloads:
       <staging>/stills/01-hall.png ... 08-hearth.png
       <staging>/video/t0-intro.mp4 ... t7-hearth.mp4

   Writes into the theme:
       theme/assets/images/home10/*.jpg
       theme/assets/video/home10/*.mp4

   WHY THIS EXISTS
   ---------------
   Two jobs: get the plates down to a sane weight, and encode the transitions so
   the browser can start one instantly and hold its last frame cleanly.

   NO LONGER ALL-INTRA. An earlier cut encoded these with -g 1 -- every frame a
   keyframe -- because home10.js scrubbed the video from the scroll position,
   assigning currentTime every frame, and seeking a normally-encoded file means
   the decoder walks back to the previous keyframe every time. All-intra made
   those seeks free, at 3-4x the file size.

   home10.js now PLAYS each transition instead of scrubbing it (scroll triggers
   the walk, it does not drive it), so the only seeks left are one to 0 when a
   walk starts and one to the end when it finishes. A keyframe every second
   serves those two perfectly well, and the files come back to roughly half the
   size. Paying 4x the bandwidth for a seek behaviour nothing does any more
   would just be a fossil of the old design.

   -movflags +faststart puts the moov atom at the front so playback can begin
   before the file has finished downloading; without it, arriving in a room can
   stall on the tail of the file.

   THE TAIL DISSOLVE
   -----------------
   Every clip gets its last TAIL seconds cross-dissolved into its own
   destination plate, so it ends on EXACTLY the frame the next clip begins on.

   This matters because home10.js never swaps in a still: it parks the video on
   its final frame for the whole dwell, so whatever the model happened to land
   on IS the room the reader sits in. Seedance treats end_image as a target to
   interpolate toward, not a frame to reproduce -- measured, the intro landed
   27% (mean abs channel difference) away from the plate that the NEXT clip
   starts from, which is a visible shove at the section boundary.

   Pinning the tail in ffmpeg fixes that for nothing, and fixes it for all eight
   at once. Re-rolling the generation would have cost credits per attempt and
   still only ever got close.
   ========================================================================= */
import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, readdirSync, statSync } from 'node:fs';
import { join, resolve, basename } from 'node:path';

const REPO = resolve(import.meta.dirname, '..');
const OUT_IMG = join(REPO, 'theme', 'assets', 'images', 'home10');
const OUT_VID = join(REPO, 'theme', 'assets', 'video', 'home10');

const STAGING = process.argv[2];
if (!STAGING || !existsSync(STAGING)) {
  console.error('usage: node tools/build-home10.mjs <staging-dir>');
  process.exit(1);
}

// ffmpeg is not a project dependency and is not assumed to be on PATH -- pass
// FFMPEG=<path> if it is somewhere unusual.
const FFMPEG = process.env.FFMPEG || 'ffmpeg';
function ff(args, label) {
  try {
    execFileSync(FFMPEG, args, { stdio: ['ignore', 'ignore', 'pipe'] });
    return true;
  } catch (e) {
    console.error(`  FAILED ${label}: ${e.stderr ? String(e.stderr).split('\n').slice(-4).join(' ') : e.message}`);
    return false;
  }
}

mkdirSync(OUT_IMG, { recursive: true });
mkdirSync(OUT_VID, { recursive: true });

const mb = (p) => (statSync(p).size / 1048576).toFixed(2) + ' MB';

// ---- stills ---------------------------------------------------------------
// 1920 wide is plenty: these are full-bleed backgrounds behind a translucent
// reading panel, never inspected closely. q=4 keeps the navy free of banding,
// which is where over-compression shows first on a large flat wall.
const stillsDir = join(STAGING, 'stills');
if (existsSync(stillsDir)) {
  console.log('stills ->', OUT_IMG);
  for (const f of readdirSync(stillsDir).filter((f) => /\.(png|jpe?g|webp)$/i.test(f)).sort()) {
    const out = join(OUT_IMG, basename(f).replace(/\.\w+$/, '.jpg'));
    if (ff(['-y', '-i', join(stillsDir, f),
            '-vf', 'scale=1920:-2:flags=lanczos',
            '-q:v', '4', out], f)) {
      console.log(`  ${basename(out)}  ${mb(out)}`);
    }
  }
}

// ---- transitions ----------------------------------------------------------

// Which plate each clip has to land on. The slug after the number matches the
// still's slug for every walk (t3-study -> 04-study); only the intro differs,
// because it arrives in the hall rather than in a room named after it.
const LANDS_ON = { intro: 'hall' };
function plateFor(videoFile) {
  const slug = LANDS_ON[basename(videoFile).replace(/^t\d+-|\.mp4$/gi, '')]
            || basename(videoFile).replace(/^t\d+-|\.mp4$/gi, '');
  const hit = readdirSync(OUT_IMG).find((s) => s.replace(/^\d+-|\.jpg$/g, '') === slug);
  return hit ? join(OUT_IMG, hit) : null;
}

const FFPROBE = process.env.FFPROBE
  || (process.env.FFMPEG ? process.env.FFMPEG.replace(/ffmpeg(\.exe)?$/i, 'ffprobe$1') : 'ffprobe');
function duration(p) {
  try {
    return parseFloat(execFileSync(FFPROBE, ['-v', 'error', '-show_entries', 'format=duration',
                                             '-of', 'default=nw=1:nk=1', p], { encoding: 'utf8' }).trim());
  } catch (e) { return NaN; }
}

const TAIL = 0.4;   // dissolve length, seconds
const HOLD = 0.25;  // plate held after it, so the parked frame is pure plate

const vidDir = join(STAGING, 'video');
if (existsSync(vidDir)) {
  console.log('transitions ->', OUT_VID);
  for (const f of readdirSync(vidDir).filter((f) => /\.mp4$/i.test(f)).sort()) {
    const out = join(OUT_VID, basename(f));
    const plate = plateFor(f);
    const dur = duration(join(vidDir, f));

    // Both inputs forced to the same 1280x720 by cover-crop, never by stretch:
    // the plates are 1.792:1 and the clips 1.778:1, and xfade refuses mismatched
    // sizes. A ~1% centre crop is invisible; distorting the room is not.
    const FIT = 'scale=1280:720:force_original_aspect_ratio=increase:flags=lanczos'
              + ',crop=1280:720,setsar=1,fps=24,format=yuv420p';

    const ENC = [
      '-an',                        // silent: nothing here has audio to carry
      '-c:v', 'libx264',
      '-profile:v', 'high',
      '-pix_fmt', 'yuv420p',        // the only chroma format every browser decodes
      // A keyframe every ~1s (24fps source). Enough for an instant seek to 0
      // and a clean park on the last frame, without paying all-intra prices.
      '-g', '24',
      '-keyint_min', '24',
      '-crf', '21',
      '-preset', 'slow',
      '-movflags', '+faststart',
      out,
    ];

    let ok, note;
    if (plate && isFinite(dur) && dur > TAIL + 0.2) {
      ok = ff(['-y', '-i', join(vidDir, f),
        '-loop', '1', '-t', String(TAIL + HOLD), '-i', plate,
        '-filter_complex',
          `[0:v]${FIT}[v0];[1:v]${FIT}[v1];` +
          `[v0][v1]xfade=transition=fade:duration=${TAIL}:offset=${(dur - TAIL).toFixed(3)}[v]`,
        '-map', '[v]', ...ENC], f);
      note = `-> ${basename(plate)}`;
    } else {
      // No matching plate, or an unreadable duration. Encode it straight rather
      // than drop it -- a clip with a soft seam still beats a missing room --
      // but say so, because a silent fallback here looks identical to success.
      console.log(`  NOTE ${basename(f)}: no tail dissolve (${plate ? 'bad duration' : 'no matching plate'})`);
      ok = ff(['-y', '-i', join(vidDir, f), '-vf', FIT, ...ENC], f);
      note = '(no dissolve)';
    }
    if (ok) { console.log(`  ${basename(out)}  ${mb(out)}  ${note}`); }
  }
}

console.log('\ndone. Home 10 reads these from assets/images/home10/ and assets/video/home10/.');
