<?php

/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 26.07.2018
 * Time: 10:34
 */

use AutoGamesDiscountCreator\Utility\Helper;

?>
<?php if (isset($data) && count($data) > 0) : ?>
    <div class="steam-content-body">
        <?php echo "Ucretsiz oyun {$data['name']}"; ?>
    </div>
    <div class="steam-cards">
        <div class="ui cards free-game">
            <div class="ui card">
                <div class="image">
                    <a href="<?php echo $data['url']; ?>"
                       target="_blank"
                    >
                        <img src="<?php echo Helper::getRemoteImage($data['url']); ?>"
                             alt="<?php echo $data['name']; ?>" width="100%">
                    </a>
                </div>
                <div class="content">
                    <a class="header"
                       href="<?php echo $data['url']; ?>"
                       target="_blank"
                    ><?php echo $data['name']; ?></a>
                    <div class="description">
                        <div>Fiyatı:
                            <strong><?php esc_html_e('ÜCRETSİZ'); ?></strong>
                        </div>
                    </div>
                </div>
                <div class="extra content">
                    <a href="<?php echo $data['url']; ?>"
                       target="_blank"
                    >
                        <i class="external icon"></i>
                        <?php echo $data['url']; ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="ui hidden divider"></div>
<?php endif; ?>

