<?php
/**
 * "Planting a Legacy" promotional flyer — a bare, iframe-embeddable page for
 * partners to drop into a website builder. Rendered by archive-cb_event.php for
 * /events/plantingalegacy/?embed=flyer with a frame-ancestors * header. It is a
 * COMPLETE standalone HTML document (no site chrome, no registration form); the
 * buttons link out to the real registration page.
 *
 * @package CB_Legacy_Luxury
 */
if (!defined('ABSPATH')) { exit; }
$img = CB_THEME_URI . '/assets/images/events/';
$reg = esc_url(home_url('/events/plantingalegacy/'));
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Planting a Legacy</title>
<style>
  body{margin:0;padding:0;background:#eceae2;-webkit-text-size-adjust:100%;}
  img{border:0;line-height:100%;outline:none;text-decoration:none;}
  table{border-collapse:collapse;}
  a{color:#22371f;}
  .flyer-wrap{max-width:600px;margin:0 auto;background:#fff;border-radius:14px;overflow:hidden;}
  @media only screen and (max-width:600px){
    .mcol{display:block !important;width:100% !important;padding:0 !important;}
    .mcol-img{width:100% !important;max-width:100% !important;height:auto !important;}
    .mtext{padding:12px 0 0 0 !important;}
    .px{padding-left:20px !important;padding-right:20px !important;}
  }
</style>
</head>
<body>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#eceae2;">
  <tr>
    <td align="center" style="padding:20px 10px;">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" class="flyer-wrap" style="width:600px;max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden;">

        <tr>
          <td align="center" bgcolor="#22371f" style="background:#22371f;padding:28px 20px 22px;font-family:Arial,Helvetica,sans-serif;">
            <div style="color:#c9a24b;font-size:12px;letter-spacing:3px;text-transform:uppercase;font-weight:bold;">Welcome to</div>
            <div style="color:#ffffff;font-size:38px;line-height:1.05;font-weight:bold;margin:6px 0 8px;">Planting a Legacy</div>
            <div style="color:#e7e3d6;font-size:14px;">Sponsored by <strong style="color:#ffffff;">Coldwell Banker Legacy</strong> &nbsp;&middot;&nbsp; Benefiting Grace Gardens</div>
          </td>
        </tr>

        <tr>
          <td style="font-size:0;line-height:0;">
            <img src="<?php echo esc_url($img . 'hero.jpg'); ?>" width="600" alt="Neighbors of all ages and abilities gardening together at sunset" style="display:block;width:100%;max-width:600px;height:auto;">
          </td>
        </tr>

        <tr>
          <td class="px" style="padding:26px 34px 8px;font-family:Arial,Helvetica,sans-serif;">
            <p style="margin:0 0 16px;color:#2a2a25;font-size:16px;line-height:1.6;">
              Join us for <strong>four hands-on gardening workshops</strong> this October and November in San Angelo. Come to one or come to all &mdash; and help us raise funds to support the mission of the non-profit <strong>Grace Gardens</strong>.
            </p>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="border-left:3px solid #c9a24b;background:#f5f2e9;padding:14px 18px;font-family:Arial,Helvetica,sans-serif;">
                  <div style="color:#7a5c12;font-size:11px;letter-spacing:2px;text-transform:uppercase;font-weight:bold;margin-bottom:6px;">Mission Statement</div>
                  <div style="color:#2a2a25;font-size:15px;line-height:1.55;font-style:italic;">&ldquo;At Grace Gardens, it&rsquo;s our mission to cultivate a welcoming, inclusive gardening space where people of all ages and abilities can grow, thrive, and experience the joy and therapeutic benefits of gardening.&rdquo;</div>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td align="center" style="padding:22px 20px 8px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
              <tr>
                <td bgcolor="#c9a24b" style="border-radius:999px;">
                  <a href="<?php echo $reg; ?>" target="_blank" rel="noopener" style="display:inline-block;padding:15px 38px;font-family:Arial,Helvetica,sans-serif;font-size:17px;font-weight:bold;color:#26331c;text-decoration:none;border-radius:999px;">Reserve Your Spot &raquo;</a>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td class="px" align="center" style="padding:26px 34px 4px;font-family:Arial,Helvetica,sans-serif;">
            <div style="color:#7a5c12;font-size:12px;letter-spacing:2px;text-transform:uppercase;font-weight:bold;">The Series</div>
            <div style="color:#22371f;font-size:24px;font-weight:bold;margin-top:4px;">Four Hands-On Workshops</div>
          </td>
        </tr>

        <?php
        $mods = [
            ['module1.jpg', 'Module 1', 'Lasagna Gardening', 'Get the knowledge and hands-on know-how to build no-dig lasagna beds that smother weeds.', 'Sat, Oct 10', '9:00&ndash;10:30 AM', '1024 N Adams St, San Angelo, TX 76901'],
            ['module2.jpg', 'Module 2', 'Composting', 'Good gardening begins with good soil: turn free scraps into rich soil with no smell or guesswork.', 'Tue, Oct 20', '5:30&ndash;7:00 PM', '3017 Knickerbocker Rd, San Angelo, TX 76904'],
            ['module3.jpg', 'Module 3', 'Upcycling &amp; Recycling', 'Turn trash into treasure: upcycle waste into useful, beautiful things, not the landfill.', 'Tue, Nov 10', '5:30&ndash;7:00 PM', '3017 Knickerbocker Rd, San Angelo, TX 76904'],
            ['module4.jpg', 'Module 4', 'Green Cleaners', 'Make non-toxic green cleaners: save money, skip chemicals, and protect your family and the planet.', 'Tue, Nov 17', '5:30&ndash;7:00 PM', '3017 Knickerbocker Rd, San Angelo, TX 76904'],
        ];
        $mi = 0;
        foreach ($mods as $m) :
            $mi++; ?>
            <tr>
              <td class="px" style="padding:16px 34px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                  <tr>
                    <td class="mcol" width="190" valign="top" style="width:190px;">
                      <img class="mcol-img" src="<?php echo esc_url($img . $m[0]); ?>" width="190" alt="<?php echo esc_attr(strip_tags($m[2])); ?>" style="display:block;width:190px;max-width:190px;height:auto;border-radius:8px;">
                    </td>
                    <td class="mcol mtext" valign="top" style="padding-left:16px;font-family:Arial,Helvetica,sans-serif;">
                      <div style="color:#7a5c12;font-size:11px;letter-spacing:1px;text-transform:uppercase;font-weight:bold;"><?php echo $m[1]; ?></div>
                      <div style="color:#22371f;font-size:18px;font-weight:bold;margin:2px 0 5px;"><?php echo $m[2]; ?></div>
                      <div style="color:#3c4034;font-size:14px;line-height:1.5;"><?php echo esc_html($m[3]); ?></div>
                      <div style="color:#22371f;font-size:13px;margin-top:9px;"><strong><?php echo $m[4]; ?></strong> &middot; <?php echo $m[5]; ?></div>
                      <div style="color:#5c5f52;font-size:13px;"><?php echo esc_html($m[6]); ?></div>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
            <?php if ($mi < count($mods)) : ?>
            <tr><td class="px" style="padding:0 34px;"><div style="border-top:1px solid #e7e3d6;font-size:0;line-height:0;">&nbsp;</div></td></tr>
            <?php endif; ?>
        <?php endforeach; ?>

        <tr>
          <td style="padding:22px 34px 8px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td align="center" bgcolor="#22371f" style="background:#22371f;border-radius:12px;padding:20px 18px;font-family:Arial,Helvetica,sans-serif;">
                  <div style="color:#c9a24b;font-size:12px;letter-spacing:2px;text-transform:uppercase;font-weight:bold;margin-bottom:6px;">Suggested Donation</div>
                  <div style="color:#ffffff;font-size:17px;line-height:1.5;">
                    <strong style="color:#c9a24b;font-size:22px;">$35</strong> per module &nbsp;&mdash;&nbsp; or <strong style="color:#c9a24b;font-size:22px;">$30</strong> per module for 3 or more
                  </div>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td align="center" style="padding:14px 20px 26px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
              <tr>
                <td bgcolor="#c9a24b" style="border-radius:999px;">
                  <a href="<?php echo $reg; ?>" target="_blank" rel="noopener" style="display:inline-block;padding:15px 38px;font-family:Arial,Helvetica,sans-serif;font-size:17px;font-weight:bold;color:#26331c;text-decoration:none;border-radius:999px;">Register Now &raquo;</a>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td align="center" bgcolor="#1a2a17" style="background:#1a2a17;padding:26px 24px;font-family:Arial,Helvetica,sans-serif;">
            <img src="<?php echo esc_url($img . 'grace-gardens.png'); ?>" width="150" alt="Grace Gardens — Nurturing All Abilities in Nature" style="display:block;width:150px;max-width:150px;height:auto;margin:0 auto 14px;border-radius:6px;">
            <div style="color:#e7e3d6;font-size:14px;line-height:1.6;">
              Questions? Contact <strong style="color:#ffffff;">Grace Gardens</strong><br>
              <a href="mailto:Martha.register1@gmail.com" style="color:#c9a24b;text-decoration:none;">Martha.register1@gmail.com</a> &nbsp;&middot;&nbsp; (325) 212-0643
            </div>
            <div style="color:#9aa593;font-size:12px;margin-top:14px;font-style:italic;">
              Sponsored by Coldwell Banker Legacy in partnership with Grace Gardens.
            </div>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
<script>
/* Post height to the host page so a partner's iframe can auto-resize to fit. */
(function () {
  var last = 0;
  function post() {
    var h = document.documentElement.scrollHeight;
    if (h && h !== last) { last = h; try { parent.postMessage({ palHeight: h }, '*'); } catch (e) {} }
  }
  window.addEventListener('load', post);
  window.addEventListener('resize', post);
  setInterval(post, 1000);
})();
</script>
</body>
</html>
<?php
return;
