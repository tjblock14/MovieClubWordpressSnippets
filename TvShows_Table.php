<?php
/*******************************************************
 * Movie Club – Consolidated Table Shortcodes (Single File)
 * - Define once, reuse for each couple via parameters.
 * - Keeps your current table structure & styling.
 *******************************************************/
/**
 * Safely implode arrays from API fields into comma-separated strings.
 * (Kept from your original code; guarded so it won't redeclare.)
 */
if (!function_exists('safe_implode')) {
    function safe_implode($field) {
        return is_array($field) ? implode(', ', $field) : $field;
    }
}

/**
 * Map a numeric rating to a background color.
 * (Return just the color string; callers handle inline style.)
 */
if (!function_exists('color_rating_cell')) {
    function color_rating_cell($rating) {
        if (!is_numeric($rating)) return '';

        if ($rating >= 9.0)                  return "#3C8D40"; // dark green
        else if ($rating >= 7.5)             return "#5F9F61"; // light green
        else if ($rating >= 6.0)             return "#A6CDA8"; // very light green
        else if ($rating >= 5.0)             return "#F3EAA3"; // yellow
        else if ($rating >= 3.5)             return "#EFB45D"; // orange
        else if ($rating >  1.0)             return "#D7572E"; // light red
        else                                 return "#AC2727"; // dark red
    }
}

/**
 * Helper: robustly read review subfields allowing for reviewer key casing differences.
 * Your API objects sometimes appear with capitalized reviewer keys for fields,
 * and lowercase for the "id". This tries several forms safely.
 */
if (!function_exists('review_value')) {
    function review_value(array $reviews, string $reviewer, string $field) {
        // Try exact, Capitalized, and lowercase reviewer keys
        $candidates = [
            $reviewer,
            ucfirst(strtolower($reviewer)),
            strtolower($reviewer),
            strtoupper($reviewer),
        ];

        foreach ($candidates as $key) {
            if (isset($reviews[$key]) && is_array($reviews[$key]) && array_key_exists($field, $reviews[$key])) {
                return $reviews[$key][$field];
            }
        }
        return '';
    }
}

/**
 * Core: register one shortcode for a couple’s table.
 *
 * @param string $shortcode   The shortcode name to register (e.g., 'tnt_table')
 * @param string $endpoint    WPGetAPI endpoint key (e.g., 'tnt_reviews')
 * @param string $reviewerA   Reviewer A key as used by your API (e.g., 'Trevor' or 'rob')
 * @param string $reviewerB   Reviewer B key as used by your API (e.g., 'Taylor' or 'terry')
 * @param string|null $labelA Optional display label for A (e.g., 'Dad'); defaults to $reviewerA
 * @param string|null $labelB Optional display label for B (e.g., 'Mom'); defaults to $reviewerB
 */
if (!function_exists('add_TvShow_table_shortcode')) 
{
    function add_TvShow_table_shortcode(string $shortcode, string $endpoint, string $reviewerA, string $reviewerB, ?string $labelA = null, ?string $labelB = null) 
    {

        add_shortcode($shortcode, function() use ($endpoint, $reviewerA, $reviewerB, $labelA, $labelB) 
        {

            // Fetch data from your configured WPGetAPI connection
            $data = wpgetapi_endpoint('movie_club_database_api', $endpoint, [
                'return' => 'body',
                'debug'  => false,
                'cache'  => false,
            ]);

            $data = json_decode($data, true);

            // Validate payload shape
            if (!is_array($data) || !isset($data['results']) || !is_array($data['results'])) {
                return '<p>Error: No data returned</p>';
            }
            $TvShows = $data['results'];

            // Normalize labels for headers (allow custom labels like "Dad"/"Mom")
            $displayA = $labelA ?? $reviewerA;
            $displayB = $labelB ?? $reviewerB;

            // ===== SORT BAR =====
            $html = '
  <div class="TvShow-sortbar" style="margin:8px 0; display:flex; gap:8px; align-items:center;">
    <label for="TvShow-sort" style="font-weight:600;">Sort by:</label>
    <select id="TvShow-sort" class="TvShow-sort">
      <option value="avg_desc">Couple Average: high → low</option>
      <option value="avg_asc">Couple Average: low → high</option>
      <option value="u1_desc">' . esc_html($displayA) . ' rating — high → low</option>
      <option value="u1_asc">' . esc_html($displayA) . ' rating — low → high</option>
      <option value="u2_desc">' . esc_html($displayB) . ' rating — high → low</option>
      <option value="u2_asc">' . esc_html($displayB) . ' rating — low → high</option>
      <option value="title_az">Title — A → Z</option>
      <option value="director_az">Director — A → Z</option>
    </select>
  </div>';

            // Build table
            $html .= '<div style="overflow-x: auto;">
   <table id="TvShow-table" data-user1="' . esc_attr(strtolower($reviewerA)) . '" data-user2="' . esc_attr(strtolower($reviewerB)) . '" style="border-collapse: collapse; min-width: 1400px; font-size: 14px; border: 2px solid #33151A;">
        <thead>
            <tr>
                 <th style="width: 150px; text-align: center; font-size: 18px; font-weight: bold; color: var(--default_table_text_color); border-collapse: collapse; border: 1px solid #33151A ;">Image</th>
                <th style="width: 150px; text-align: center; font-size: 18px; font-weight: bold; color: var(--default_table_text_color); border-collapse: collapse; border: 1px solid #33151A ;">Title</th>
                <th style="width: 200px; text-align: center; font-size: 18px; font-weight: bold; color: var(--default_table_text_color); border-collapse: collapse; border: 1px solid #33151A;">Summary</th>
                <th style="width: 250px; text-align: center; font-size: 18px; font-weight: bold; color: var(--default_table_text_color); border-collapse: collapse; border: 1px solid #33151A;">Genres</th>
                <th style="width: 200px; text-align: center; font-size: 18px; font-weight: bold; color: var(--default_table_text_color); border-collapse: collapse; border: 1px solid #33151A;">Premiered</th>
                <th style="width: 200px; text-align: center; font-size: 18px; font-weight: bold; color: var(--default_table_text_color); border-collapse: collapse; border: 1px solid #33151A;">Creators</th>
                <th style="width: 200px; text-align: center; font-size: 18px; font-weight: bold; color: var(--default_table_text_color); border-collapse: collapse; border: 1px solid #33151A;">Status</th>
                <th style="width: 200px; text-align: center; font-size: 18px; font-weight: bold; color: var(--default_table_text_color); border-collapse: collapse; border: 1px solid #33151A;"># of Seasons</th>
                <th style="width: 100px; font-size: 18px; font-weight: bold; color: var(--default_table_text_color); border-collapse: collapse; border: 1px solid  #33151A;">' . esc_html($displayA) . ' Rating</th>
                <th style="width: 150px; font-size: 18px; font-weight: bold; color: var(--default_table_text_color); border-collapse: collapse; border: 1px solid #33151A;">' . esc_html($displayA) . ' Comments</th>
                <th style="width: 100px; font-size: 18px; font-weight: bold; color: var(--default_table_text_color); border-collapse: collapse; border: 1px solid  #33151A;">' . esc_html($displayB) . ' Rating</th>
                <th style="width: 150px; font-size: 18px; font-weight: bold; color: var(--default_table_text_color); border-collapse: collapse; border: 1px solid  #33151A;">' . esc_html($displayB) . ' Comments</th>
                <th style="width: 150px; font-size: 18px; font-weight: bold; color: var(--default_table_text_color); border-collapse: collapse; border: 1px solid  #33151A;">' . esc_html($displayA) . ' and ' . esc_html($displayB) . ' Average</th>
            </tr>
        </thead>
        <tbody>';

            foreach ($TvShows as $TvShow)
            {
                $TvShow_id = $TvShow['id'];
                $title     = $TvShow['title'] ?? '';

                // NEW: get seasons array for nested rendering
                $seasons   = $TvShow['seasons'] ?? [];

                // MAIN SHOW ROW
                $html .= '<tr class="tvshow-row" data-show-id="' . esc_attr($TvShow_id) . '">'; // NEW class + data attribute

                // Image cell
                $html .= '<td style="width: 250px; text-align: center; vertical-align: middle; font-size: 14px; color:#FBFCEE; border-collapse: collapse; border: 1px solid black;">'
                            . '<img src="' . esc_url($TvShow['image_url']) . '" alt="' . esc_attr($TvShow['title']) . '" '
                            . 'style="max-width: 120px; height: auto; border-radius: 4px;" />'
                            . '</td>';

                // NEW: Title cell now has arrow + tvshow-main-cell class
                $html .= '<td class="title-cell tvshow-main-cell" style="width: 200px; text-align: center; vertical-align: middle; font-size: 18px; font-weight: bold; color: #FBFCEE; border-collapse: collapse; border: 1px solid black;">'
                        . '<span class="tvshow-title">' . esc_html($title) . '</span>'
                        . '<span class="tv-toggle tv-toggle-seasons" data-target="seasons" data-show-id="' . esc_attr($TvShow_id) . '">▼</span>'
                        . '</td>';

                // (Your original "summary" column currently uses creators-cell; unchanged)
                $html .= '<td class="creators-cell" style="width: 200px; text-align: center; vertical-align: middle; font-size: 14px; color: #FBFCEE; border-collapse: collapse; border: 1px solid black;">' . esc_html(safe_implode($TvShow['creators'] ?? '')) . '</td>';
                $html .= '<td style="width: 200px; text-align: center; vertical-align: middle; font-size: 14px; font-weight: bold; color: #FBFCEE; border-collapse: collapse; border: 1px solid black;">' . esc_html(safe_implode($TvShow['genres']      ?? '')) . '</td>';

                $html .= '<td style="width: 200px; text-align: center; vertical-align: middle; font-size: 14px; font-weight: bold; color: #FBFCEE; border-collapse: collapse; border: 1px solid black;">' . esc_html(safe_implode($TvShow['premiered']   ?? '')) . '</td>';
                $html .= '<td style="width: 200px; text-align: center; vertical-align: middle; font-size: 14px; font-weight: bold; color: #FBFCEE; border-collapse: collapse; border: 1px solid black;">' . esc_html(safe_implode($TvShow['creators']    ?? '')) . '</td>';
                $html .= '<td style="width: 200px; text-align: center; vertical-align: middle; font-size: 14px; font-weight: bold; color: #FBFCEE; border-collapse: collapse; border: 1px solid black;">' . esc_html(safe_implode($TvShow['status']      ?? '')) . '</td>';
                $html .= '<td style="width: 200px; text-align: center; vertical-align: middle; font-size: 14px; font-weight: bold; color: #FBFCEE; border-collapse: collapse; border: 1px solid black;">' . esc_html(safe_implode($TvShow['num_seasons'] ?? '')) . '</td>';

                // Reviews block
                $reviews = $TvShow['reviews'] ?? [];

                // Reviewer A values
                $user1_rating = review_value($reviews, $reviewerA, 'rating');
                $user1_review = review_value($reviews, $reviewerA, 'review');
                $user1_id     = review_value($reviews, $reviewerA, 'id');
                $user1_color  = color_rating_cell($user1_rating);
                $user1_data_reviewer = strtolower($reviewerA);

                $html .= '<td class="rating-cell" data-review-type="tv" data-target-type="show" data-couple-slug="TrevorTaylor" data-reviewer="' . esc_attr($user1_data_reviewer) . '" data-id="' . esc_attr($TvShow_id) . '" data-tv-show-title="' . esc_attr($title) . '" data-review-id="' . esc_attr($user1_id) . '" data-rating="' . esc_attr($user1_rating) . '" style="background-color: ' . esc_attr($user1_color) . ';">' . esc_html($user1_rating) . '</td>';

                $html .= '<td class="review-cell" data-review-type="tv" data-target-type="show" data-couple-slug="TrevorTaylor" data-reviewer="' . esc_attr($user1_data_reviewer) . '" data-id="' . esc_attr($TvShow_id) . '" data-tv-show-title="' . esc_attr($title) . '" data-review-id="' . esc_attr($user1_id) . '">' . esc_html($user1_review) . '</td>';

                // Reviewer B values
                $user2_rating = review_value($reviews, $reviewerB, 'rating');
                $user2_review = review_value($reviews, $reviewerB, 'review');
                $user2_id     = review_value($reviews, $reviewerB, 'id');
                $user2_color  = color_rating_cell($user2_rating);
                $user2_data_reviewer = strtolower($reviewerB);

                $html .= '<td class="rating-cell" data-review-type="tv" data-target-type="show" data-couple-slug="TrevorTaylor" data-reviewer="' . esc_attr($user2_data_reviewer) . '" data-id="' . esc_attr($TvShow_id) . '" data-tv-show-title="' . esc_attr($title) . '" data-review-id="' . esc_attr($user2_id) . '" data-rating="' . esc_attr($user2_rating) . '" style="background-color: ' . esc_attr($user2_color) . ';">' . esc_html($user2_rating) . '</td>';

                $html .= '<td class="review-cell" data-review-type="tv" data-target-type="show" data-couple-slug="TrevorTaylor" data-reviewer="' . esc_attr($user2_data_reviewer) . '" data-id="' . esc_attr($TvShow_id) . '" data-tv-show-title="' . esc_attr($title) . '" data-review-id="' . esc_attr($user2_id) . '">' . esc_html($user2_review) . '</td>';

                // Calculate average rating
                $avg_rating = (is_numeric($user1_rating) && is_numeric($user2_rating))
                               ? round(($user1_rating + $user2_rating) / 2, 2) : '';
                $avg_color = color_rating_cell($avg_rating);

                $html .= '<td class="avg-cell rating-cell" data-rating="' . esc_attr($avg_rating) . '" style="background-color: ' . esc_attr($avg_color) . ';">' 
                          . esc_html($avg_rating) . '</td>';

                $html .= '</tr>';

                // NEW: NESTED SEASONS + EPISODES ROW (one per show)
                $html .= '<tr class="tvshow-seasons-row is-collapsed" data-show-id="' . esc_attr($TvShow_id) . '">
                            <td colspan="13" style="padding:0; border: none;">
                                <div class="seasons-wrapper">';

                if (!empty($seasons)) {
                    $html .= '<table class="seasons-table" style="width:100%; border-collapse:collapse; margin-top:4px;">
                                <thead>
                                    <tr>
                                        <th style="width: 30px; text-align:left; padding:4px; color: var(--default_table_text_color);">Season</th>
                                        <th style="width: 30px; text-align:left; padding:4px; color: var(--default_table_text_color);">Episodes</th>
                                        <th style="width: 100px; text-align:left; padding:4px; color: var(--default_table_text_color);">Summary</th>
                                        <th style="width: 50px; text-align:left; padding:4px; color: var(--default_table_text_color);">Release Year</th>
                                        <th style="width: 50px; font-size: 18px; font-weight: bold; color: var(--default_table_text_color); border-collapse: collapse;">' . esc_html($displayA) . ' Season Rating</th>
                                        <th style="width: 100px; font-size: 18px; font-weight: bold; color: var(--default_table_text_color); border-collapse: collapse;">' . esc_html($displayA) . ' Comments</th>
                                        <th style="width: 50px; font-size: 18px; font-weight: bold; color: var(--default_table_text_color); border-collapse: collapse;">' . esc_html($displayB) . ' Season Rating</th>
                                        <th style="width: 100px; font-size: 18px; font-weight: bold; color: var(--default_table_text_color); border-collapse: collapse;">' . esc_html($displayB) . ' Comments</th>
                                        <th style="width: 50px; font-size: 18px; font-weight: bold; color: var(--default_table_text_color); border-collapse: collapse;">' . esc_html($displayA) . ' and ' . esc_html($displayB) . ' Season Average</th>
                                    </tr>
                                </thead>
                                <tbody>';

                    foreach ($seasons as $season) {
                        $season_id  = $season['id'] ?? 0;
                        $season_num     = $season['season_number'] ?? '';
                        $season_ReleaseYr    = $season['season_release_year'] ?? ''; 
                        $season_episode_count   = $season['season_episode_cnt'] ?? '';
                        $season_summary = wp_kses_post($season['summary'] ?? ''); # Do this for summary to remove the surrounding <p p>
                        $episodes   = $season['episodes'] ?? [];

                        // Reviews block
                        $season_reviews = $season['reviews'] ?? [];

                        $user1_season_rating = review_value($season_reviews, $reviewerA, 'rating');
                        $user1_season_review = review_value($season_reviews, $reviewerA, 'review');
                        $user1_season_id = review_value($season_reviews, $reviewerA, 'id');
                        $user1_season_rtg_color  = color_rating_cell($user1_season_rating);

                        $user2_season_rating = review_value($season_reviews, $reviewerB, 'rating');
                        $user2_season_review = review_value($season_reviews, $reviewerB, 'review');
                        $user2_season_id = review_value($season_reviews, $reviewerB, 'id');
                        $user2_season_rtg_color  = color_rating_cell($user2_season_rating);

                        //$user2_season_rating = x;

                        // Season row
                        $html .= '<tr class="season-row" data-season-id="' . esc_attr($season_id) . '">
                                    <td style="padding:4px; color: var(--default_table_text_color)">
                                        <span>S' . esc_html($season_num) . '</span> 
                                        <span class="tv-toggle tv-toggle-episodes" data-target="episodes" data-season-id="' . esc_attr($season_id) . '">▼</span>
                                    </td>
                                    <td style="padding:4px; color: var(--default_table_text_color);">' . esc_html($season_episode_count) . '</td>
                                    <td style="padding:4px; color: var(--default_table_text_color);">' . $season_summary . '</td>
                                    <td style="color: var(--default_table_text_color); padding:4px;">' . esc_html($season_ReleaseYr) . '</td>';
                                  

                        $html .= '<td class="rating-cell" data-review-type="tv" data-target-type="season" data-couple-slug="TrevorTaylor" data-show-id="' . esc_attr($TvShow_id) . '" data-reviewer="' . esc_attr($user1_data_reviewer) . '" data-id="' . esc_attr($season_id) . '" data-tv-show-title="' . esc_attr($title) . '" data-review-id="' . esc_attr($user1_season_id) . '" data-rating="' . esc_attr($user1_season_rating) . '" style="background-color: ' . esc_attr($user1_season_rtg_color) . ';">' . esc_html($user1_season_rating) . '</td>';
                        $html .= '<td class="review-cell" data-review-type="tv" data-target-type="season" data-couple-slug="TrevorTaylor" data-show-id="' . esc_attr($TvShow_id) . '" data-reviewer="' . esc_attr($user1_data_reviewer) . '" data-id="' . esc_attr($season_id) . '" data-tv-show-title="' . esc_attr($title) . '" data-review-id="' . esc_attr($user1_season_id) . '">' . esc_html($user1_season_review) . '</td>';

                        $html .= '<td class="rating-cell" data-review-type="tv" data-target-type="season" data-couple-slug="TrevorTaylor" data-show-id="' . esc_attr($TvShow_id) . '" data-reviewer="' . esc_attr($user2_data_reviewer) . '" data-id="' . esc_attr($season_id) . '" data-tv-show-title="' . esc_attr($title) . '" data-review-id="' . esc_attr($user2_season_id) . '" data-rating="' . esc_attr($user2_season_rating) . '" style="background-color: ' . esc_attr($user2_season_rtg_color) . ';">' . esc_html($user2_season_rating) . '</td>';
                        $html .= '<td class="review-cell" data-review-type="tv" data-target-type="season" data-couple-slug="TrevorTaylor" data-show-id="' . esc_attr($TvShow_id) . '" data-reviewer="' . esc_attr($user2_data_reviewer) . '" data-id="' . esc_attr($season_id) . '" data-tv-show-title="' . esc_attr($title) . '" data-review-id="' . esc_attr($user2_season_id) . '">' . esc_html($user2_season_review) . '</td>';

                        // Calculate average rating
                        $avg_season_rating = (is_numeric($user1_season_rating) && is_numeric($user2_season_rating))
                                      ? round(($user1_season_rating + $user2_season_rating) / 2, 2) : '';
                        $avg_season_color = color_rating_cell($avg_season_rating);

                        $html .= '<td class="avg-cell rating-cell" data-rating="' . esc_attr($avg_season_rating) . '" style="background-color: ' . esc_attr($avg_season_color) . ';">' 
                                  . esc_html($avg_season_rating) . '</td>';

                        $html .= '</tr>';

                        // Episodes row for this season
                        $html .= '<tr class="season-episodes-row is-collapsed" data-season-id="' . esc_attr($season_id) . '">
                                    <td colspan="4" style="padding:4px 4px 8px 20px; ">';

                        if (!empty($episodes)) 
                        {
                            $html .= '<table class="episodes-table" style="width:100%; border-collapse:collapse; margin-top:4px;">
                                        <thead>
                                            <tr>
                                                <th style="text-align:left; padding:3px; color: var(--default_table_text_color);">Episode</th>
                                                <th style="text-align:left; padding:3px; color: var(--default_table_text_color);">Title</th>
                                                <th style="text-align:left; padding:3px; color: var(--default_table_text_color);">Summary</th>
                                                <th style="text-align:left; padding:3px; color: var(--default_table_text_color);">Runtime (mins)</th>
                                                <th style="text-align:left; padding:3px; color: var(--default_table_text_color);">Air Date</th>
                                                <th style="width: 50px; font-size: 18px; font-weight: bold; color: var(--default_table_text_color); border-collapse: collapse;">' . esc_html($displayA) . ' Episode Rating</th>
                                                <th style="width: 100px; font-size: 18px; font-weight: bold; color: var(--default_table_text_color); border-collapse: collapse;">' . esc_html($displayA) . ' Comments</th>
                                                <th style="width: 50px; font-size: 18px; font-weight: bold; color: var(--default_table_text_color); border-collapse: collapse;">' . esc_html($displayB) . ' Episode Rating</th>
                                                <th style="width: 100px; font-size: 18px; font-weight: bold; color: var(--default_table_text_color); border-collapse: collapse;">' . esc_html($displayB) . ' Comments</th>
                                                <th style="width: 50px; font-size: 18px; font-weight: bold; color: var(--default_table_text_color); border-collapse: collapse;">' . esc_html($displayA) . ' and ' . esc_html($displayB) . ' Episode Average</th>
                                            </tr>
                                        </thead>
                                        <tbody>';

                            foreach ($episodes as $episode) 
                            {
                                $episode_id  = $episode['id'] ?? 0;
                                $episode_number = $episode['episode_number'] ?? '';
                                $episode_AirDate    = $episode['air_date'] ?? ''; 
                                $episode_title = $episode['episode_title'] ?? '';
                                $episode_runtime = $episode['episode_runtime'] ?? '';
                                $episode_summary = wp_kses_post($episode['summary'] ?? ''); # Do this for summary to remove the surrounding <p p>

                                // Reviews block
                                $episode_reviews = $episode['reviews'] ?? [];

                                $user1_episode_rating = review_value($episode_reviews, $reviewerA, 'rating');
                                $user1_episode_review = review_value($episode_reviews, $reviewerA, 'review');
                                $user1_episode_id = review_value($episode_reviews, $reviewerA, 'id');
                                $user1_episode_rtg_color  = color_rating_cell($user1_episode_rating);

                                $user2_episode_rating = review_value($episode_reviews, $reviewerB, 'rating');
                                $user2_episode_review = review_value($episode_reviews, $reviewerB, 'review');
                                $user2_episode_id = review_value($episode_reviews, $reviewerB, 'id');
                                $user2_episode_rtg_color  = color_rating_cell($user2_episode_rating);

                                $html .= '<tr class="episode-row">
                                            <td style="padding:3px; color: var(--default_table_text_color);">' . esc_html($episode_number) . '</td>
                                            <td style="padding:3px; color: var(--default_table_text_color);">' . esc_html($episode_title) . '</td>
                                            <td style="padding:3px; color: var(--default_table_text_color);">' . $episode_summary . '</td>
                                            <td style="padding:3px; color: var(--default_table_text_color);">' . esc_html($episode_runtime) . '</td>
                                            <td style="padding:3px; color: var(--default_table_text_color);">' . esc_html($episode_AirDate) . '</td>';

                                $html .= '<td class="rating-cell" data-review-type="tv" data-target-type="episode" data-couple-slug="TrevorTaylor" data-show-id="' . esc_attr($TvShow_id) . '" data-reviewer="' . esc_attr($user1_data_reviewer) . '" data-id="' . esc_attr($episode_id) . '" data-tv-show-title="' . esc_attr($title) . '" data-review-id="' . esc_attr($user1_episode_id) . '" data-rating="' . esc_attr($user1_episode_rating) . '" style="background-color: ' . esc_attr($user1_episode_rtg_color) . ';">' . esc_html($user1_episode_rating) . '</td>';
                                $html .= '<td class="review-cell" data-review-type="tv" data-target-type="episode" data-couple-slug="TrevorTaylor" data-show-id="' . esc_attr($TvShow_id) . '" data-reviewer="' . esc_attr($user1_data_reviewer) . '" data-id="' . esc_attr($episode_id) . '" data-tv-show-title="' . esc_attr($title) . '" data-review-id="' . esc_attr($user1_episode_id) . '">' . esc_html($user1_episode_review) . '</td>';

                                $html .= '<td class="rating-cell" data-review-type="tv" data-target-type="episode" data-couple-slug="TrevorTaylor" data-show-id="' . esc_attr($TvShow_id) . '" data-reviewer="' . esc_attr($user2_data_reviewer) . '" data-id="' . esc_attr($episode_id) . '" data-tv-show-title="' . esc_attr($title) . '" data-review-id="' . esc_attr($user2_episode_id) . '" data-rating="' . esc_attr($user2_episode_rating) . '" style="background-color: ' . esc_attr($user2_episode_rtg_color) . ';">' . esc_html($user2_episode_rating) . '</td>';
                                $html .= '<td class="review-cell" data-review-type="tv" data-target-type="episode" data-couple-slug="TrevorTaylor" data-show-id="' . esc_attr($TvShow_id) . '" data-reviewer="' . esc_attr($user2_data_reviewer) . '" data-id="' . esc_attr($episode_id) . '" data-tv-show-title="' . esc_attr($title) . '" data-review-id="' . esc_attr($user2_episode_id) . '">' . esc_html($user2_episode_review) . '</td>';

                              // Calculate average rating
                              $avg_episode_rating = (is_numeric($user1_episode_rating) && is_numeric($user2_episode_rating))
                                            ? round(($user1_episode_rating + $user2_episode_rating) / 2, 2) : '';
                              $avg_episode_color = color_rating_cell($avg_episode_rating);

                              $html .= '<td class="avg-cell rating-cell" data-rating="' . esc_attr($avg_episode_rating) . '" style="background-color: ' . esc_attr($avg_episode_color) . ';">' 
                                        . esc_html($avg_episode_rating) . '</td>';

                                $html .= '</tr>';
                            }

                            $html .= '        </tbody>
                                      </table>';
                        } else {
                            $html .= '<em>No episodes found for this season.</em>';
                        }

                        $html .= '      </td>
                                  </tr>';
                    }

                    $html .= '    </tbody>
                              </table>';
                } else {
                    $html .= '<em>No seasons found for this show.</em>';
                }

                $html .= '        </div>
                            </td>
                          </tr>';
                // END NEW nested row
            }

            $html .= '</tbody></table></div>';

            // ===== SORTING SCRIPT =====
            $html .= '
<script>
(function() {
  function asNumber(v) 
  {
    if (v === null || v === undefined) return NaN;
    const n = parseFloat(String(v).replace(",", "."));
    return Number.isFinite(n) ? n : NaN;
  }

  function text(el) 
  {
    return (el && el.textContent || "").trim();
  }

  function getReviewerCell(row, reviewer) 
  {
    return row.querySelector(\'.rating-cell[data-reviewer="\' + reviewer + \'"]\');
  }

  function getAvgCell(row) 
  {
    return row.querySelector(".avg-cell");
  }

  function getKey(row, mode, u1, u2) 
  {
    switch (mode) 
    {
      case "u1_desc":
      case "u1_asc": 
        {
            const c = getReviewerCell(row, u1);
            return asNumber(c ? c.dataset.rating : NaN);
        }

      case "u2_desc" :
      case "u2_asc"  : 
        {
            const c = getReviewerCell(row, u2);
            return asNumber(c ? c.dataset.rating : NaN);
        }

      case "avg_desc":
      case "avg_asc": 
        {
            const c = getAvgCell(row);
            return asNumber(c ? c.dataset.rating : NaN);
        }
      case "title_az":
        return text(row.querySelector(".title-cell")).toLowerCase();
      
      case "director_az":
        return text(row.querySelector(".director-cell")).toLowerCase();
      
      default:
        return "";
    }
  }

  function compareValues(a, b, numeric, asc) 
  {
    if (numeric) 
    {
      const aNaN = Number.isNaN(a), bNaN = Number.isNaN(b);
      if (aNaN && bNaN) return 0;
      if (aNaN) return 1;     // push NaN to bottom
      if (bNaN) return -1;
      return asc ? a - b : b - a;
    } 
    else 
    {
      if (a === b) return 0;
      if (asc) return a < b ? -1 : 1;
      return a > b ? -1 : 1;
    }
  }

  function sortTable(mode) 
  {
    const table = document.getElementById("TvShow-table");
    if (!table) 
        return;

    const u1 = (table.dataset.user1 || "").toLowerCase();
    const u2 = (table.dataset.user2 || "").toLowerCase();
    const tbody = table.querySelector("tbody") || table;

    const rows = Array.from(tbody.querySelectorAll("tr"));
    const indexed = rows.map((row, idx) => ({ row, idx, key: getKey(row, mode, u1, u2) }));

    const numericModes = new Set(["u1_desc","u1_asc","u2_desc","u2_asc","avg_desc","avg_asc"]);
    const asc = mode.endsWith("_asc");
    const isNumeric = numericModes.has(mode);

    indexed.sort((A, B) => 
    {
      const cmp = compareValues(A.key, B.key, isNumeric, asc);
      return cmp !== 0 ? cmp : (A.idx - B.idx); // stable
    });

    indexed.forEach(item => tbody.appendChild(item.row));
  }

  document.addEventListener("DOMContentLoaded", function() 
  {
    const sel = document.getElementById("TvShow-sort");
    if (!sel) return;

    sel.addEventListener("change", function() 
    {
      sortTable(this.value);
    });

    // Optional: default sort on load
    // sortTable(sel.value);
  });
})();
</script>';

            // NEW: small CSS for nested rows + toggles
            $html .= '
<style>
  .is-collapsed { display: none; }
  .tvshow-main-cell { cursor: pointer; }
  .season-row { cursor: pointer; }
  .tv-toggle {
    display: inline-block;
    margin-left: 4px;
    font-size: 10px;
    cursor: pointer;
  }
  .seasons-wrapper {
    margin-top: 4px;
    border-left: 2px solid rgba(255,255,255,0.2);
    padding-left: 8px;
  }
</style>';

            // NEW: JS handler to toggle seasons + episodes
            $html .= '
<script>
(function() {
  document.addEventListener("click", function(event) {
    let toggle = event.target.closest(".tv-toggle");

    // If not clicking directly on arrow, see if we clicked the title cell or season row
    if (!toggle) {
      const showCell = event.target.closest(".tvshow-main-cell");
      if (showCell) {
        toggle = showCell.querySelector(".tv-toggle-seasons");
      } else {
        const seasonRow = event.target.closest(".season-row");
        if (seasonRow) {
          toggle = seasonRow.querySelector(".tv-toggle-episodes");
        }
      }
      if (!toggle) return;
    }

    const target = toggle.dataset.target;

    if (target === "seasons") {
      const showId = toggle.dataset.showId;
      const row = document.querySelector(\'.tvshow-seasons-row[data-show-id="\' + showId + \'"]\');
      if (!row) return;
      const isCollapsed = row.classList.contains("is-collapsed");
      row.classList.toggle("is-collapsed", !isCollapsed);
    }

    if (target === "episodes") {
      const seasonId = toggle.dataset.seasonId;
      const row = document.querySelector(\'.season-episodes-row[data-season-id="\' + seasonId + \'"]\');
      if (!row) return;
      const isCollapsed = row.classList.contains("is-collapsed");
      row.classList.toggle("is-collapsed", !isCollapsed);
    }
  });
})();
</script>';

             // ===== TVMaze CREDIT (required attribution) =====
            $html .= '<div class="tvmaze-credit" style="font-size:12px; margin-top:8px; opacity:0.7;">
               TV Show Data provided by <a href="https://www.tvmaze.com" target="_blank" rel="noopener noreferrer">TVmaze.com</a>.
            </div>';

            return $html;
        });
    }
}

/*******************************************************
 * Register your four tables using the single function.
 * If you ever add a new couple, just add one new line.
 *******************************************************/

// Trevor / Taylor (labels match names)
add_TvShow_table_shortcode('tnt_tv_show_table', 'tnt_tv_reviews', 'Trevor', 'Taylor');

// Marissa / Nathan (labels match names)
add_TvShow_table_shortcode('mn_tv_show_table', 'mn_tv_reviews', 'Marissa', 'Nathan');

// Sierra / Benett (labels match names)
add_TvShow_table_shortcode('sb_tv_show_table', 'sb_tv_reviews', 'Sierra', 'Benett');

// Dad (Rob) / Mom (Terry): custom display labels different from API reviewer keys
add_TvShow_table_shortcode('mom_dad_tv_show_table', 'mom_dad_tv_reviews', 'Rob', 'Terry', 'Dad', 'Mom');

// Mia and Logan
add_TvShow_table_shortcode('mia_logan_tv_show_table', 'mia_logan_tv_reviews', 'Mia', 'Logan');
