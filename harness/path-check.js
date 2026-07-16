/*
 * Samples the REAL poseAt() out of home8.js and checks the walk is actually
 * smooth. Screenshots prove the poses; only this proves the motion between them.
 *
 * The functions are private to the IIFE, so rather than re-typing them here (which
 * would test my transcription, not the shipping file) this brace-matches them out
 * of the source and evals that exact text.
 */
const fs = require('fs');
const SRC = fs.readFileSync(require('path').join(__dirname, '..', 'theme', 'assets', 'js', 'cb-home8', 'home8.js'), 'utf8');

function grabFn(name) {
  const i = SRC.indexOf('function ' + name + '(');
  if (i < 0) throw new Error('function not found: ' + name);
  let depth = 0;
  for (let k = SRC.indexOf('{', i); k < SRC.length; k++) {
    if (SRC[k] === '{') depth++;
    else if (SRC[k] === '}') { depth--; if (depth === 0) return SRC.slice(i, k + 1); }
  }
  throw new Error('unbalanced: ' + name);
}
function grabDecl(startsWith) {
  const re = new RegExp('^\\s*var ' + startsWith + '[\\s\\S]*?;\\s*$', 'm');
  const m = SRC.match(re);
  if (!m) throw new Error('decl not found: ' + startsWith);
  return m[0];
}

const parts = [
  grabDecl('HALL_X'), grabDecl('ROOM_D'), grabDecl('ROOM_H'), grabDecl('ROOM_IN'),
  grabDecl('ROOM = \\['), grabDecl('U_DWELL'),
  grabFn('clamp'), grabFn('clamp01'), grabFn('smooth'), grabFn('smoother'), grabFn('band'),
  grabFn('yawFor'), grabFn('roomPose'), grabFn('poseAt'),
  'return { poseAt: poseAt, ROOM: ROOM, U_DWELL: U_DWELL, ROOM_IN: ROOM_IN, band: band };'
];
const api = new Function(parts.join('\n'))();
const { poseAt, ROOM, U_DWELL, ROOM_IN } = api;

const N = ROOM.length, DEG = 180 / Math.PI;
const out = {};
const sample = (g) => { poseAt(g, out); return { x: out.x, z: out.z, yaw: out.yaw }; };

// Dense sweep of the whole walk.
const STEP = 0.0005, samples = [];
for (let g = 0; g <= N - 1 + 1e-9; g += STEP) samples.push({ g, ...sample(g) });

let fail = 0;
const report = (ok, label, detail) => {
  if (!ok) fail++;
  console.log((ok ? 'OK   ' : 'FAIL ') + label.padEnd(46) + (detail || ''));
};

// A "speed" that mixes translation and rotation -- yaw weighted so a 90deg turn
// counts comparably to the ~8 units of travel it accompanies.
const speeds = [];
for (let i = 1; i < samples.length; i++) {
  const a = samples[i - 1], b = samples[i];
  speeds.push({ g: b.g, v: (Math.hypot(b.x - a.x, b.z - a.z) + Math.abs(b.yaw - a.yaw) * 6) / STEP });
}

// Spikes, not magnitudes. A discontinuity is a step wildly out of line with its
// neighbours; a large-but-smooth step is just the camera moving quickly, which is
// the entire point. (First cut of this test compared max step against an invented
// constant and "failed" on the yaw peak -- measuring speed, not continuity.)
const spikeCheck = (series, key, label) => {
  const vals = series.map((s) => s[key]).filter((v) => v > 1e-9).sort((a, b) => a - b);
  const med = vals[Math.floor(vals.length / 2)] || 1e-9;
  let worst = 0, at = 0;
  for (const s of series) { const r = s[key] / med; if (r > worst) { worst = r; at = s.g; } }
  report(worst < 8, label, `peak ${worst.toFixed(1)}x median @ g=${at.toFixed(3)}`);
};
spikeCheck(speeds, 'v', 'C0/C1: no velocity spike (no teleport)');

const accs = [];
for (let i = 1; i < speeds.length; i++) {
  accs.push({ g: speeds[i].g, a: Math.abs(speeds[i].v - speeds[i - 1].v) / STEP });
}
spikeCheck(accs, 'a', 'C2: no acceleration spike (no jerk)');

// THE point of this change, tested precisely.
//
// Four smoothstepped legs meant velocity returned to zero at every boundary --
// six dead stops per room. But "speed is low" is NOT the bug: leaving a dwell the
// camera is genuinely at rest and accelerating, and smootherstep departs very
// gently by design. Thresholding on magnitude just re-flags that (and tempts you
// to widen the margin until it passes, which tests nothing).
//
// The bug has a shape: the camera speeds up, slows back toward zero BETWEEN legs,
// then speeds up again. That is an interior local MINIMUM in speed. A clean walk
// accelerates away from the room, cruises, and decelerates into the next one --
// one hump, no dips. So: find the dips.
// Threshold reasoning, since this is the number that decides whether the walk is
// "smooth": a DEAD STOP -- the thing the overlapping arc exists to remove -- reads
// as ~0-5% of peak. The mid-hallway glide sits at ~24% and always will: in optical
// terms one radian of yaw sweeps the view about as much as TWELVE units of forward
// travel, so a 90-degree turn inherently outpaces an 8-unit glide, and equalising
// them would only make the turn glacial. So the assertion is "speed never returns
// toward a standstill", not "speed is uniform". 15% is a generous margin over the
// ~5% a real stall shows, and still comfortably below the honest 24% trough.
const DIP = 0.15;
for (let i = 0; i < N - 1; i++) {
  const seg = speeds.filter((s) => s.g > i + U_DWELL + 0.005 && s.g < i + 1 - U_DWELL - 0.005);
  if (!seg.length) { continue; }
  const peak = Math.max(...seg.map((s) => s.v));
  // Ignore the outermost ramps -- those are the departure and the arrival.
  const inner = seg.filter((s, k) => k > seg.length * 0.12 && k < seg.length * 0.88);
  let dips = [];
  for (let k = 1; k < inner.length - 1; k++) {
    const a = inner[k - 1].v, b = inner[k].v, c = inner[k + 1].v;
    if (b <= a && b <= c && b < peak * DIP) { dips.push(inner[k]); }
  }
  const trough = Math.min(...inner.map((s) => s.v));
  report(dips.length === 0, `walk ${i} -> ${i + 1}: no stall between legs`,
    dips.length ? `dip to v=${dips[0].v.toFixed(1)} @ g=${dips[0].g.toFixed(3)} (peak ${peak.toFixed(1)})`
                : `trough ${(trough / peak * 100).toFixed(0)}% of peak -- one continuous arc`);
}

// 4. The dwell must be exactly flat, or s != 1 and the text goes soft.
for (let i = 0; i < N; i++) {
  const a = sample(Math.max(0, i - U_DWELL + 1e-6)), b = sample(i), c = sample(Math.min(N - 1, i + U_DWELL - 1e-6));
  const flat = Math.hypot(a.x - b.x, a.z - b.z) < 1e-6 && Math.hypot(c.x - b.x, c.z - b.z) < 1e-6
            && Math.abs(a.yaw - b.yaw) < 1e-9 && Math.abs(c.yaw - b.yaw) < 1e-9;
  report(flat, `dwell ${i} (${ROOM[i].theme}) is flat`, flat ? '' : 'pose moves during the read');
}

// 5. Each room is actually entered: yaw reaches its full turn, camera steps inside.
for (let i = 0; i < N; i++) {
  const p = sample(i), want = ROOM[i].side * ROOM_IN;
  report(Math.abs(p.x - want) < 1e-6 && Math.abs(p.z - ROOM[i].z) < 1e-6,
    `room ${i} entered (x=${want}, z=${ROOM[i].z})`, `got x=${p.x.toFixed(2)} z=${p.z.toFixed(2)} yaw=${(p.yaw * DEG).toFixed(1)}deg`);
}

// 6. The hallway must actually BE a hallway: somewhere between two rooms you are
//    on the centre line, squared up with the corridor. If that never happens, you
//    never really left the room and the walk is a smear between two doorways.
//
//    Sampling the exact midpoint (the first version of this) asserted more than
//    the design promises. Leaving the threshold the turn deliberately starts early
//    -- there is no back-out leg to spend that window on -- so at the midpoint the
//    camera is ~11 degrees into its turn. Anticipating a doorway is the arc
//    working, not a defect. What matters is that a squared-up stretch exists.
for (let i = 0; i < N - 1; i++) {
  const seg = samples.filter((s) => s.g > i && s.g < i + 1);
  const square = seg.filter((s) => Math.abs(s.x) < 0.05 && Math.abs(s.yaw) < 0.02);
  const widthG = square.length * STEP;
  report(widthG > 0.05, `hallway between ${i} and ${i + 1}: squared up`,
    square.length
      ? `aligned for ${widthG.toFixed(3)} of g (g=${square[0].g.toFixed(2)}..${square[square.length - 1].g.toFixed(2)})`
      : 'never faces down the hallway');
}

console.log('\n' + (fail === 0 ? 'WALK IS SMOOTH -- all checks passed' : fail + ' CHECK(S) FAILED'));
process.exit(fail === 0 ? 0 : 1);

