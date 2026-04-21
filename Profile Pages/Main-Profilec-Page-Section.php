<?php
add_shortcode('movie_club_profile', function($atts = [])
{
    ob_start();
    ?>
    <div class="mc-profile-wrap" id="mc-profile-root">

        <div class="mc-profile-loading" id="mc-profile-loading">
            Loading profile...
        </div>

        <div class="mc-profile-error" id="mc-profile-error" style="display:none;">
            Unable to load profile data.
        </div>

        <div class="mc-profile-content" id="mc-profile-content" style="display:none;">

            <div class="mc-profile-header">
                <div class="mc-profile-avatar" id="mc-profile-avatar">?</div>

                <div class="mc-profile-header-main">
                    <h2 class="mc-profile-name" id="mc-profile-name">Profile</h2>
                    <div class="mc-profile-subtitle" id="mc-profile-subtitle">Your Movie Club Stats</div>
                </div>

                <div class="mc-profile-header-side">
                    <div class="mc-profile-big-stat">
                        <span class="mc-profile-big-stat-value" id="mc-overall-avg">--</span>
                        <span class="mc-profile-big-stat-label">Overall Avg</span>
                    </div>
                </div>
            </div>

            <div class="mc-profile-grid mc-profile-grid-top">
                <div class="mc-profile-card">
                    <div class="mc-profile-card-label">Total Reviews</div>
                    <div class="mc-profile-card-value" id="mc-total-reviews">--</div>
                </div>

                <div class="mc-profile-card">
                    <div class="mc-profile-card-label">Movie Average</div>
                    <div class="mc-profile-card-value" id="mc-movie-avg">--</div>
                </div>

                <div class="mc-profile-card">
                    <div class="mc-profile-card-label">TV Average</div>
                    <div class="mc-profile-card-value" id="mc-tv-avg">--</div>
                </div>

                <div class="mc-profile-card">
                    <div class="mc-profile-card-label">Current Streak</div>
                    <div class="mc-profile-card-value" id="mc-current-streak">--</div>
                </div>

                <div class="mc-profile-card">
                    <div class="mc-profile-card-label">Longest Streak</div>
                    <div class="mc-profile-card-value" id="mc-longest-streak">--</div>
                </div>

                <div class="mc-profile-card">
                    <div class="mc-profile-card-label">Perfect 10s</div>
                    <div class="mc-profile-card-value" id="mc-perfect-tens">--</div>
                </div>
            </div>

            <div class="mc-profile-section">
                <h3 class="mc-profile-section-title">Streaks</h3>

                <div class="mc-profile-grid mc-profile-grid-streaks">
                    <div class="mc-profile-card">
                        <div class="mc-profile-card-label">Overall Current</div>
                        <div class="mc-profile-card-value" id="mc-current-streak-2">--</div>
                    </div>

                    <div class="mc-profile-card">
                        <div class="mc-profile-card-label">Overall Longest</div>
                        <div class="mc-profile-card-value" id="mc-longest-streak-2">--</div>
                    </div>

                    <div class="mc-profile-card">
                        <div class="mc-profile-card-label">Movie Current</div>
                        <div class="mc-profile-card-value" id="mc-current-movie-streak">--</div>
                    </div>

                    <div class="mc-profile-card">
                        <div class="mc-profile-card-label">Movie Longest</div>
                        <div class="mc-profile-card-value" id="mc-longest-movie-streak">--</div>
                    </div>

                    <div class="mc-profile-card">
                        <div class="mc-profile-card-label">TV Current</div>
                        <div class="mc-profile-card-value" id="mc-current-tv-streak">--</div>
                    </div>

                    <div class="mc-profile-card">
                        <div class="mc-profile-card-label">TV Longest</div>
                        <div class="mc-profile-card-value" id="mc-longest-tv-streak">--</div>
                    </div>
                </div>
            </div>

            <div class="mc-profile-section">
                <h3 class="mc-profile-section-title">Favorites & Fun Stats</h3>

                <div class="mc-profile-grid mc-profile-grid-fun">
                    <div class="mc-profile-card mc-profile-card-wide">
                        <div class="mc-profile-card-label">Favorite Genre</div>
                        <div class="mc-profile-card-value mc-profile-card-value-text" id="mc-favorite-genre">--</div>
                    </div>

                    <div class="mc-profile-card mc-profile-card-wide">
                        <div class="mc-profile-card-label">Highest Rated Movie</div>
                        <div class="mc-profile-card-value mc-profile-card-value-text" id="mc-highest-movie">--</div>
                    </div>

                    <div class="mc-profile-card mc-profile-card-wide">
                        <div class="mc-profile-card-label">Highest Rated TV Show</div>
                        <div class="mc-profile-card-value mc-profile-card-value-text" id="mc-highest-show">--</div>
                    </div>

                    <div class="mc-profile-card mc-profile-card-wide">
                        <div class="mc-profile-card-label">Latest Review</div>
                        <div class="mc-profile-card-value mc-profile-card-value-text" id="mc-latest-review">--</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <?php
    return ob_get_clean();
});