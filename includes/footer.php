<!-- Footer -->
<footer class="footer" style="background-color:#000; padding: 10px">
    <div class="container">
        <div style="display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 1rem;">
            <!-- Logo and Brand -->
            <div style="display: flex; align-items: center; gap: 1rem;">
                <?php
                $logo_path = 'assets/images/logo.png';
                $base_path = '';

                // Determine correct path based on current location
                if (strpos($_SERVER['REQUEST_URI'], '/auth/') !== false ||
                    strpos($_SERVER['REQUEST_URI'], '/customer/') !== false ||
                    strpos($_SERVER['REQUEST_URI'], '/seller/') !== false ||
                    strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
                    $logo_path = '../assets/images/logo.png';
                }

                if (file_exists($logo_path)): ?>
                    <img src="<?php echo $logo_path; ?>" alt="DesiDukaan" style="height: 40px; width: auto;">
                <?php else: ?>
                    <span style="font-size: 2rem;">🏪</span>
                <?php endif; ?>

                <div style="text-align: center;">
                    <h3 style="color: white; margin: 0; font-size: 1.5rem; font-weight: bold;">DesiDukaan</h3>
                    <p style="color: rgba(255, 255, 255, 0.7); margin: 0; font-size: 0.9rem; font-style: italic;">
                        <?php echo t('slogan'); ?>
                    </p>
                </div>
            </div>

            <!-- Credits -->
            <div style="text-align: center;">
                <p style="margin: 0; font-size: 1rem; font-weight: 500; color: white">
                    Made with <span class="heart">❤️</span> by 21 Software Students MUET
                </p>
            </div>
        </div>

        <!-- Decorative Heart Background -->
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 8rem; color: rgba(255, 255, 255, 0.05); z-index: 0; pointer-events: none;">
            💖
        </div>
    </div>
</footer>

<style>
    .footer {
        position: relative;
        overflow: hidden;
    }

    .footer .container {
        position: relative;
        z-index: 1;
    }
</style>
