<?php
/**
 * "Plant a Legacy" event landing — Coldwell Banker Legacy × Grace Gardens.
 *
 * Rendered by archive-cb_event.php for /events/ (full, with site chrome) and for
 * /events/?embed=1 (bare, for a partner iframe). Self-contained: all styles are
 * inline and scoped under .pal-event so it renders identically in both contexts
 * and inside a third-party page without depending on which stylesheets loaded.
 *
 * Graphics are optional-with-fallback: drop the client art into
 * assets/images/events/ (main.*, hero.*, qr.*) and it is used automatically;
 * until then an on-brand designed hero and a placeholder QR stand in. The module
 * schedule comes from cb_event_modules() (inc/form-handler.php) so the page and
 * the notification emails can never disagree.
 *
 * @package CB_Legacy_Luxury
 */
if (!defined('ABSPATH')) { exit; }

$pal_modules = function_exists('cb_event_modules') ? cb_event_modules() : [];

/** First existing asset for a basename across common extensions → URL, else ''. */
$pal_img = function ($base) {
    foreach (['jpg', 'jpeg', 'png', 'webp', 'svg'] as $ext) {
        $rel = '/assets/images/events/' . $base . '.' . $ext;
        if (file_exists(CB_THEME_DIR . $rel)) {
            return esc_url(CB_THEME_URI . $rel . '?v=' . (function_exists('cb_asset_ver') ? cb_asset_ver('assets/images/events/' . $base . '.' . $ext) : '1'));
        }
    }
    return '';
};
$pal_main  = $pal_img('main');   // full infographic (optional top banner)
$pal_hero  = $pal_img('hero');   // hero background photo (optional)
$pal_qr    = $pal_img('qr');     // exact QR supplied by client (optional)
$pal_donate = trim((string) get_theme_mod('cb_grace_donate_url', ''));
?>
<style>
/* ===== Plant a Legacy — scoped landing styles ===================== */
.pal-event{--pal-green:#22371f;--pal-green-2:#1a2a17;--pal-cream:#f5f2e9;--pal-gold:#c9a24b;--pal-ink:#2a2a25;--pal-line:rgba(255,255,255,.18);
  color:var(--pal-ink);background:var(--pal-cream);font-family:'Familjen Grotesk','Roboto',system-ui,sans-serif;line-height:1.55;overflow-x:hidden;}
.pal-event *{box-sizing:border-box;}
.pal-wrap{max-width:1080px;margin:0 auto;padding:0 1.25rem;}
.pal-event img{max-width:100%;height:auto;display:block;}

/* main infographic banner */
.pal-main{background:var(--pal-green-2);text-align:center;padding:0;}
.pal-main img{margin:0 auto;width:100%;max-width:1080px;}

/* hero */
.pal-hero{position:relative;color:#fff;text-align:center;padding:clamp(3.5rem,9vw,7rem) 1.25rem;
  background:linear-gradient(180deg,#2c4526 0%,#22371f 55%,#18271410 100%),radial-gradient(120% 90% at 50% 0%,#38562f 0%,#22371f 60%);}
.pal-hero--photo{background:none;}
.pal-hero__bg{position:absolute;inset:0;background-size:cover;background-position:center;}
.pal-hero__scrim{position:absolute;inset:0;background:linear-gradient(180deg,rgba(20,32,16,.42),rgba(20,32,16,.72));}
.pal-hero__in{position:relative;max-width:900px;margin:0 auto;}
.pal-hero__eyebrow{display:inline-block;letter-spacing:.28em;text-transform:uppercase;font-size:.72rem;font-weight:600;color:var(--pal-gold);margin-bottom:1rem;}
.pal-hero h1{font-size:clamp(2.7rem,8vw,5rem);line-height:1.02;margin:0 0 .5rem;font-weight:700;letter-spacing:-.01em;text-shadow:0 2px 24px rgba(0,0,0,.25);}
.pal-hero__sub{font-size:clamp(1.05rem,2.6vw,1.5rem);font-weight:500;opacity:.96;margin:0 auto;}
.pal-hero__rule{display:flex;align-items:center;justify-content:center;gap:.9rem;margin:1.4rem auto 1.1rem;max-width:560px;color:rgba(255,255,255,.55);}
.pal-hero__rule::before,.pal-hero__rule::after{content:"";height:1px;flex:1;background:var(--pal-line);}
.pal-hero__meta{font-size:.82rem;letter-spacing:.14em;text-transform:uppercase;font-weight:600;color:rgba(255,255,255,.85);}
.pal-hero__meta b{color:var(--pal-gold);font-weight:700;}

/* CTA strip */
.pal-cta{background:var(--pal-green);color:#fff;}
.pal-cta__in{display:flex;flex-wrap:wrap;align-items:center;gap:1.5rem;justify-content:center;padding:1.6rem 1.25rem;text-align:center;}
.pal-cta__qr{background:#fff;border-radius:14px;padding:.55rem;width:120px;flex:none;box-shadow:0 8px 24px rgba(0,0,0,.25);}
.pal-cta__qr img{width:100%;border-radius:6px;}
.pal-cta__qr span{display:block;color:var(--pal-green);font-size:.62rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;text-align:center;margin-top:.35rem;}
.pal-cta__copy{max-width:22rem;text-align:left;}
.pal-cta__copy h2{margin:0 0 .3rem;font-size:1.35rem;font-weight:700;}
.pal-cta__copy p{margin:0;opacity:.85;font-size:.95rem;}
.pal-btns{display:flex;flex-wrap:wrap;gap:.7rem;justify-content:center;}
.pal-btn{display:inline-flex;align-items:center;gap:.5rem;border-radius:999px;padding:.85rem 1.6rem;font-weight:700;font-size:1rem;
  text-decoration:none;border:2px solid transparent;transition:transform .15s ease,box-shadow .15s ease,background .15s ease;cursor:pointer;}
.pal-btn:hover{transform:translateY(-2px);}
.pal-btn--book{background:var(--pal-gold);color:#26331c;box-shadow:0 8px 22px rgba(201,162,75,.35);}
.pal-btn--donate{background:#c62828;color:#fff;box-shadow:0 8px 22px rgba(198,40,40,.32);}
.pal-btn--ghost{background:transparent;color:#fff;border-color:rgba(255,255,255,.55);}

/* sections */
.pal-sec{padding:clamp(2.6rem,6vw,4.5rem) 0;}
.pal-sec--tint{background:#fff;}
.pal-head{text-align:center;max-width:640px;margin:0 auto clamp(1.8rem,4vw,2.8rem);}
.pal-head__eyebrow{letter-spacing:.22em;text-transform:uppercase;font-size:.72rem;font-weight:700;color:var(--pal-gold);}
.pal-head h2{font-size:clamp(1.8rem,4.5vw,2.6rem);margin:.4rem 0 .6rem;color:var(--pal-green);font-weight:700;}
.pal-head p{color:#5c5f52;margin:0;font-size:1.05rem;}

/* module cards */
.pal-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.1rem;}
.pal-card{background:#fff;border:1px solid #e7e3d6;border-radius:16px;overflow:hidden;box-shadow:0 6px 18px rgba(30,49,31,.06);display:flex;flex-direction:column;}
.pal-card__badge{background:var(--pal-green);color:var(--pal-gold);font-size:.68rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase;padding:.5rem 1rem;}
.pal-card__body{padding:1.1rem 1.15rem 1.25rem;display:flex;flex-direction:column;gap:.5rem;flex:1;}
.pal-card h3{margin:0;font-size:1.28rem;color:var(--pal-green);font-weight:700;}
.pal-card__tag{font-style:italic;color:#6a6d5e;margin:0;}
.pal-card__meta{margin-top:auto;display:flex;flex-direction:column;gap:.3rem;font-size:.9rem;color:#3c4034;padding-top:.6rem;border-top:1px dashed #e0dccc;}
.pal-card__meta b{color:var(--pal-green);}
.pal-card__row{display:flex;gap:.5rem;align-items:flex-start;}
.pal-card__row svg{flex:none;margin-top:.15rem;color:var(--pal-gold);}
.pal-card__take{font-size:.82rem;color:#7a7d6d;margin:0;}

/* instructor */
.pal-inst{display:grid;grid-template-columns:minmax(0,1fr);gap:1.5rem;align-items:start;max-width:820px;margin:0 auto;}
.pal-inst__card{background:#fff;border:1px solid #e7e3d6;border-radius:18px;padding:clamp(1.4rem,3.5vw,2.2rem);box-shadow:0 8px 26px rgba(30,49,31,.07);}
.pal-inst__eyebrow{display:inline-block;background:var(--pal-green);color:#fff;font-size:.72rem;letter-spacing:.14em;text-transform:uppercase;font-weight:700;padding:.35rem .9rem;border-radius:999px;}
.pal-inst h3{font-size:clamp(1.6rem,4vw,2.1rem);color:var(--pal-green);margin:.8rem 0 .1rem;font-weight:700;}
.pal-inst__role{font-style:italic;color:#7a7d6d;margin:0 0 1rem;font-size:1.05rem;}
.pal-inst p{margin:0 0 .9rem;color:#41453a;}

/* registration form */
.pal-reg{background:var(--pal-green);color:#fff;}
.pal-reg .pal-head h2{color:#fff;}
.pal-reg .pal-head p{color:rgba(255,255,255,.8);}
.pal-form{max-width:680px;margin:0 auto;background:rgba(255,255,255,.04);border:1px solid var(--pal-line);border-radius:18px;padding:clamp(1.3rem,4vw,2.2rem);}
.pal-field{margin-bottom:1.1rem;}
.pal-field label{display:block;font-weight:600;font-size:.9rem;margin-bottom:.4rem;letter-spacing:.02em;}
.pal-field input[type=text],.pal-field input[type=email],.pal-field input[type=tel]{width:100%;padding:.85rem 1rem;border-radius:10px;border:1px solid rgba(255,255,255,.28);background:rgba(255,255,255,.96);color:#23291d;font-size:1rem;font-family:inherit;}
.pal-field input:focus{outline:2px solid var(--pal-gold);outline-offset:1px;}
.pal-row2{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
.pal-checks{display:grid;gap:.6rem;}
.pal-check{display:flex;gap:.75rem;align-items:flex-start;background:rgba(255,255,255,.06);border:1px solid var(--pal-line);border-radius:12px;padding:.85rem 1rem;cursor:pointer;transition:background .15s ease,border-color .15s ease;}
.pal-check:hover{background:rgba(255,255,255,.1);}
.pal-check input{margin-top:.2rem;width:18px;height:18px;flex:none;accent-color:var(--pal-gold);}
.pal-check__t{font-weight:700;}
.pal-check__d{display:block;font-weight:400;opacity:.82;font-size:.86rem;margin-top:.15rem;}
.pal-hp{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;}
.pal-submit{width:100%;margin-top:.4rem;background:var(--pal-gold);color:#26331c;border:0;border-radius:999px;padding:1rem 1.5rem;font-size:1.05rem;font-weight:800;cursor:pointer;transition:transform .15s ease,box-shadow .15s ease;}
.pal-submit:hover{transform:translateY(-2px);box-shadow:0 10px 26px rgba(201,162,75,.4);}
.pal-submit:disabled{opacity:.7;cursor:default;transform:none;}
.pal-status{min-height:1.4em;margin-top:.9rem;text-align:center;font-weight:600;background:#fff;border-radius:10px;padding:0;transition:padding .15s ease;}
.pal-status:not(:empty){padding:.7rem 1rem;}

/* partner logos strip */
.pal-logos{background:var(--pal-green-2);color:#fff;text-align:center;padding:2.2rem 1.25rem;}
.pal-logos img{height:52px;width:auto;display:inline-block;margin:0 1.2rem;vertical-align:middle;opacity:.95;}
.pal-logos__tag{margin-top:1rem;font-style:italic;color:rgba(255,255,255,.7);}

@media (max-width:640px){
  .pal-row2{grid-template-columns:1fr;}
  .pal-cta__copy{text-align:center;}
  .pal-cta__in{flex-direction:column;}
}
</style>

<div class="pal-event">

    <?php /* Optional: the full client infographic as a top banner, if uploaded. */ ?>
    <?php if ($pal_main) : ?>
        <div class="pal-main"><img src="<?php echo $pal_main; ?>" alt="Plant a Legacy — Coldwell Banker Legacy and Grace Gardens: four hands-on workshops, October–November, San Angelo."></div>
    <?php endif; ?>

    <?php /* HERO — uses the client hero photo if present, else an on-brand design. */ ?>
    <header class="pal-hero<?php echo $pal_hero ? ' pal-hero--photo' : ''; ?>">
        <?php if ($pal_hero) : ?>
            <div class="pal-hero__bg" style="background-image:url('<?php echo $pal_hero; ?>');"></div>
            <div class="pal-hero__scrim"></div>
        <?php endif; ?>
        <div class="pal-hero__in">
            <span class="pal-hero__eyebrow">Grow · Learn · Make a Difference</span>
            <h1>Plant a Legacy</h1>
            <p class="pal-hero__sub">Coldwell Banker Legacy&nbsp;&times;&nbsp;Grace Gardens</p>
            <div class="pal-hero__rule"><span>&#127807;</span></div>
            <p class="pal-hero__meta"><b>Four</b> hands-on workshops &nbsp;&bull;&nbsp; <b>October&ndash;November</b> &nbsp;&bull;&nbsp; San Angelo</p>
        </div>
    </header>

    <?php /* CTA strip: scan-to-register QR + Book + (optional) Donate. */ ?>
    <section class="pal-cta">
        <div class="pal-cta__in">
            <?php if ($pal_qr) : ?>
                <a class="pal-cta__qr" href="#pal-register" aria-label="Scan or tap to register">
                    <img src="<?php echo $pal_qr; ?>" alt="QR code — scan to register for Plant a Legacy">
                    <span>Scan to register</span>
                </a>
            <?php endif; ?>
            <div class="pal-cta__copy">
                <h2>Reserve your spot</h2>
                <p>Free, hands-on, and open to the community. Register below &mdash; every workshop supports Grace Gardens.</p>
            </div>
            <div class="pal-btns">
                <a class="pal-btn pal-btn--book" href="#pal-register">&#128197;&nbsp; Book Your Spot</a>
                <?php if ($pal_donate) : ?>
                    <a class="pal-btn pal-btn--donate" href="<?php echo esc_url($pal_donate); ?>" target="_blank" rel="noopener">&#10084;&nbsp; Donate</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php /* WORKSHOP SCHEDULE — the four modules from the single source of truth. */ ?>
    <section class="pal-sec pal-sec--tint">
        <div class="pal-wrap">
            <div class="pal-head">
                <span class="pal-head__eyebrow">The Series</span>
                <h2>Four Hands-On Workshops</h2>
                <p>Come to one or come to all. Each session sends you home with something you made and the know-how to do it again.</p>
            </div>
            <div class="pal-grid">
                <?php foreach ($pal_modules as $m) :
                    $card = $pal_img('module' . $m['n']); ?>
                    <article class="pal-card">
                        <?php if ($card) : ?><div class="pal-card__image"><img src="<?php echo $card; ?>" alt="<?php echo esc_attr('Module ' . $m['n'] . ' — ' . $m['title']); ?>"></div><?php endif; ?>
                        <div class="pal-card__badge">Module <?php echo (int) $m['n']; ?></div>
                        <div class="pal-card__body">
                            <h3><?php echo esc_html($m['title']); ?></h3>
                            <p class="pal-card__tag"><?php echo esc_html($m['tagline']); ?></p>
                            <div class="pal-card__meta">
                                <span class="pal-card__row"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg><span><b><?php echo esc_html($m['day'] . ', ' . $m['date']); ?></b> &middot; <?php echo esc_html($m['time']); ?></span></span>
                                <span class="pal-card__row"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 12-9 12s-9-5-9-12a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg><span><?php echo esc_html($m['address']); ?></span></span>
                            </div>
                            <p class="pal-card__take">&#127793; You&rsquo;ll take home: <?php echo esc_html($m['takehome']); ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <p style="text-align:center;font-style:italic;color:var(--pal-green);font-size:1.15rem;margin-top:2rem;">&ldquo;Hands in the soil. Hope in the future.&rdquo;</p>
        </div>
    </section>

    <?php /* INSTRUCTOR — Carina Corbet-Owen (client-supplied bio). */ ?>
    <section class="pal-sec">
        <div class="pal-wrap">
            <div class="pal-inst">
                <div class="pal-inst__card">
                    <span class="pal-inst__eyebrow">Your Instructor</span>
                    <h3>Carina Corbet-Owen</h3>
                    <p class="pal-inst__role">Master composter, food grower, recycler, and upcycler.</p>
                    <p>Hi, I&rsquo;m Carina. I got involved with Grace Gardens while searching for land to grow food for our local San Angelo food bank. When Martha said this could fit her vision for Grace Gardens, I jumped in boots and all. Why start another garden when I could pour my energy into one already underway? The fact that three siblings were involved sealed it &mdash; it spoke straight to my love of family.</p>
                    <p>The mission &mdash; &ldquo;nurturing all abilities in nature&rdquo; &mdash; hit home because of a powerful experience with a young autistic man. He went from making mud twirls on the sidelines to becoming part of the building team. That was living proof of what Mother Nature can do, and I know in my bones that Grace Gardens can have the same powerful effect on the lives of people with special needs.</p>
                </div>
            </div>
        </div>
    </section>

    <?php /* REGISTRATION FORM — posts to cb_event_registration (AJAX). */ ?>
    <section class="pal-sec pal-reg" id="pal-register">
        <div class="pal-wrap">
            <div class="pal-head">
                <span class="pal-head__eyebrow">Register</span>
                <h2>Save Your Spot</h2>
                <p>Tell us who&rsquo;s coming and which workshops you&rsquo;d like to attend.</p>
            </div>
            <form class="pal-form" id="cb-event-registration-form" novalidate>
                <div class="pal-field">
                    <label for="pal-name">Full Name</label>
                    <input type="text" id="pal-name" name="full_name" autocomplete="name" required>
                </div>
                <div class="pal-row2">
                    <div class="pal-field">
                        <label for="pal-email">Email Address</label>
                        <input type="email" id="pal-email" name="email" autocomplete="email" required>
                    </div>
                    <div class="pal-field">
                        <label for="pal-phone">Phone Number</label>
                        <input type="tel" id="pal-phone" name="phone" autocomplete="tel">
                    </div>
                </div>
                <div class="pal-field">
                    <label>Which workshops will you attend? <span style="font-weight:400;opacity:.75;">(check all that apply)</span></label>
                    <div class="pal-checks">
                        <?php foreach ($pal_modules as $key => $m) : ?>
                            <label class="pal-check">
                                <input type="checkbox" name="modules[]" value="<?php echo esc_attr($key); ?>">
                                <span>
                                    <span class="pal-check__t">Module <?php echo (int) $m['n']; ?> &mdash; <?php echo esc_html($m['title']); ?></span>
                                    <span class="pal-check__d"><?php echo esc_html($m['day'] . ', ' . $m['date'] . ' · ' . $m['time'] . ' · ' . $m['address']); ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="pal-hp" aria-hidden="true">
                    <label>Company<input type="text" name="company" tabindex="-1" autocomplete="off"></label>
                </div>
                <button type="submit" class="pal-submit">Register Now</button>
                <p class="pal-status" id="cb-event-reg-status" role="status" aria-live="polite"></p>
            </form>
        </div>
    </section>

    <?php /* PARTNER LOGOS. */ ?>
    <div class="pal-logos">
        <img src="<?php echo esc_url(CB_THEME_URI . '/assets/images/logos/monogram-horizontal-stacked.svg'); ?>" alt="Coldwell Banker Legacy">
        <?php if ($grace = $pal_img('grace-gardens')) : ?><img src="<?php echo $grace; ?>" alt="Grace Gardens — Nurturing All Abilities in Nature"><?php endif; ?>
        <p class="pal-logos__tag">Nurturing all abilities in nature.</p>
    </div>

</div>
