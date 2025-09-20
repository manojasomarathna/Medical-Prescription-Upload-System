</main> <?php // Main content ends here ?>
    
    <footer style="margin-top: 4rem; padding: 2rem 0; background-color: #343a40; color: white; text-align: center;">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Medical Prescription System. All rights reserved.</p>
            <p>Developed for secure prescription management</p>
        </div>
    </footer>

    <script src="<?php echo isset($js_path) ? $js_path : '../js/'; ?>script.js"></script>
    
    <?php if (isset($extra_js)): ?>
        <?php foreach ($extra_js as $js_file): ?>
            <script src="<?php echo $js_file; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>