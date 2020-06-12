<?php

/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 26.07.2018
 * Time: 10:34
 */

use Rambouillet\Util\Helper;

?>
<?php if (isset($free_game_data) && count($free_game_data) > 0) : ?>
    <div class="steam-content-body">
        <?php echo "Ucretsiz oyun {$free_game_data['name']}"; ?>
    </div>
    <div class="steam-cards">
        <div class="ui cards free-game">
            <div class="ui card">
                <div class="image">
                    <a href="<?php echo $free_game_data['url']; ?>"
                       target="_blank"
                    >
                        <img src="<?php echo Helper::getRemoteImage($free_game_data['url']); ?>"
                             alt="<?php echo $free_game_data['name']; ?>" width="100%">
                    </a>
                </div>
                <div class="content">
                    <a class="header"
                       href="<?php echo $free_game_data['url']; ?>"
                       target="_blank"
                    ><?php echo $free_game_data['name']; ?></a>
                    <div class="description">
                        <div>Fiyatı:
                            <strong><?php echo 'ÜCRETSİZ'; ?></strong>
                        </div>
                    </div>
                </div>
                <div class="extra content">
                    <a href="<?php echo $free_game_data['url']; ?>"
                       target="_blank"
                    >
                        <i class="external icon"></i>
                        <?php echo $free_game_data['url']; ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="ui hidden divider"></div>
<?php endif; ?>

