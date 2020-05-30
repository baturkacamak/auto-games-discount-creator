<?php

/**
 * Created by PhpStorm.
 * User: baturkacamak
 * Date: 26.07.2018
 * Time: 10:34
 */

?>
<?php if (isset($game_data) && count($game_data) > 0) : ?>
    <div class="steam-content-body">
        <?php echo sprintf(
            '%s\'nde bugün alınmaya değer
        toplam %d oyun var.',
            $post_title,
            count($game_data)
        ); ?>
    </div>
    <div class="steam-cards">
        <div class="ui cards">
            <?php foreach ($game_data as $item) :
                ?>
                <div class="ui card">
                    <div class="image">
                        <a href="<?php echo $item['url']; ?>"
                           target="_blank"
                        >
                            <img src="<?php echo $item['thumbnail_url']; ?>">
                        </a>
                    </div>
                    <div class="content">
                        <a class="header"
                           href="<?php echo $item['url']; ?>"
                           target="_blank"
                        ><?php echo $item['name']; ?></a>
                        <div class="description">
                            <div>Fiyatı:
                                <strong><?php echo $item['price']; ?></strong> TL
                            </div>
                            <div>İndirim Oranı: <strong><?php echo $item['cut']; ?></strong>%</div>
                        </div>
                    </div>
                    <div class="extra content">
                        <a href="<?php echo $item['url']; ?>"
                           target="_blank"
                        >
                            <i class="steam icon"></i>
                            <?php echo $item['url']; ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

