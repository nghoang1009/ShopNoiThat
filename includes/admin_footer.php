    </main>
</div> <!-- admin-main -->
</div> <!-- admin-wrapper -->

<script src="<?php echo asset_url('assets/js/admin/admin.js'); ?>"></script>
<?php if (isset($extraJs)): ?>
    <?php foreach ($extraJs as $js): ?>
        <script src="<?php echo asset_url($js); ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
