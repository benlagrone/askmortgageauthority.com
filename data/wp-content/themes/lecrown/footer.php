<footer id="colophon" class="site-footer" role="contentinfo">
    <div class="footer-widget-area">
		
		<?php if (is_page('your-form-page-slug')) : ?>
<script>
  console.log('Forminator script loaded.');
</script>
<?php endif; ?>
		
        <?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
            <?php dynamic_sidebar( 'footer-1' ); ?>
        <?php else : ?>
            <div class="footer-default-text">
                <p>&copy; <?php echo date('Y'); ?> Ask Mortgage Authority. All rights reserved.</p>
            </div>
        <?php endif; ?>
    </div>

</footer><!-- #colophon -->

</div><!-- #page -->

<?php wp_footer(); ?>
<script src="https://askmortgageauthority.com/wp-content/themes/lecrown/chat.js"></script>
</body>
</html>