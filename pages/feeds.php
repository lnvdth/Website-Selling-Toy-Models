<main id="feeds">
    <h1>Tin Tức</h1>
    <div class="row">
        <?php
        $result = $ketnoi->query('SELECT feedTitle, feedContent, feedImage FROM feeds ORDER BY feedId DESC');
        $index = 0;
        while ($feed = $result->fetch_assoc()):
            if ($index < 3):
                $class = $index % 2 === 0 ? 'feed-large-left' : 'feed-large-right';
        ?>
                <article class="feed-large <?= $class ?>">
                    <img src="<?= e($feed['feedImage']) ?>" alt="<?= e($feed['feedTitle']) ?>" loading="lazy">
                    <div class="feed-text">
                        <h2><?= e($feed['feedTitle']) ?></h2>
                        <p><?= nl2br(e($feed['feedContent'])) ?></p>
                    </div>
                </article>
            <?php else: ?>
                <article class="feed-small">
                    <img src="<?= e($feed['feedImage']) ?>" alt="<?= e($feed['feedTitle']) ?>" loading="lazy">
                    <h3><?= e($feed['feedTitle']) ?></h3>
                </article>
            <?php
            endif;
            $index++;
        endwhile;
        ?>
    </div>
</main>