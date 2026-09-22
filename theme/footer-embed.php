<?php
/**
 * Bare footer for the iframe-embeddable /events/?embed=1 view. Loaded via
 * get_footer('embed'). Just closes out wp_footer() and the document.
 *
 * @package CB_Legacy_Luxury
 */
if (!defined('ABSPATH')) { exit; }
?>
<?php wp_footer(); ?>
<script>
/* Post this embed's height to the host page so a partner's iframe can auto-resize
   to fit (see the shareable snippet). Harmless if the host ignores it. */
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
