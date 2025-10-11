<div id="feeds">
    <h1>Tin Tức</h1>
    <div class="row">
        <?php 
            $query = "SELECT * FROM FEEDS";
            $result = mysqli_query($ketnoi, $query);

            $index = 0;
            while ($feed = mysqli_fetch_assoc($result)) {
                if ($index < 3) {
                    $class = ($index % 2 == 0) ? "feed-large-left" : "feed-large-right";
                    echo '
                    <div class="feed-large ' . $class . '">
                        <img src="' . $feed['feedImage'] . '" alt="">
                        <div class="feed-text">
                            <h2>' . $feed['feedTitle'] . '</h2>
                            <p>' . $feed['feedContent'] . '</p>
                        </div>
                    </div>';
                } else {
                    echo '
                    <div class="feed-small">
                        <img src="' . $feed['feedImage'] . '" alt="">
                        <h3>' . $feed['feedTitle'] . '</h3>
                    </div>';
                }
                $index++;
            }
        ?>
    </div>
</div>