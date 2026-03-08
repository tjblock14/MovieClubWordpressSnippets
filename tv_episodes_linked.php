<?php

add_shortcode('tv_episodes_view', function() 
{
    $couple    = sanitize_text_field($_GET['couple'] ?? '');
    $show_id   = intval($_GET['show_id'] ?? 0);
    $season_id = intval($_GET['season_id'] ?? 0);

    $return_to = isset($_GET['return_to']) ? rawurldecode($_GET['return_to']) : '';

    $cfg = tv_couple_config($couple);
    if (!$cfg)
    {
        return '<p>Error: Invalid couple.</p>';
    }

    if (!$show_id || !$season_id)
    {
        return '<p>Error: Missing show_id or season_id.</p>';
    }

    $resp = wpgetapi_endpoint('movie_club_database_api', $cfg['endpoint'], [
        'return' => 'body',
        'debug'  => false,
        'cache'  => false,
    ]);

    $data = json_decode($resp, true);
    if (!is_array($data) || !isset($data['results']) || !is_array($data['results'])) 
    {
        return '<p>Error: No data returned</p>';
    }

    // Find show
    $show = null;
    foreach ($data['results'] as $s) 
    {
        if (intval($s['id'] ?? 0) === $show_id)
        { 
            $show = $s; 
            break; 
        }
    }

    if (!$show)
    {
        return '<p>Error: Show not found.</p>';
    }

    // Find season
    $season = null;
    foreach (($show['seasons'] ?? []) as $sea) 
    {
        if (intval($sea['id'] ?? 0) === $season_id) 
        {
            $season = $sea; 
            break; 
        }
    }

    if (!$season)
    {
        return '<p>Error: Season not found.</p>';
    }

    $seasons_page = tv_page_url_by_slug('tv-show-seasons');

    $back_to_seasons = tv_build_url($seasons_page, [
        'couple'    => $couple,
        'show_id'   => $show_id,
        'return_to' => rawurlencode($return_to),
    ]);

    $show_title_raw = $show['title'] ?? '';
    $show_title     = esc_html($show_title_raw);
    $season_num     = esc_html($season['season_number'] ?? '');
    $episodes       = $season['episodes'] ?? [];

    $html  = '<div style="margin:10px 0;">';
    $html .= '<a href="' . esc_url($back_to_seasons) . '">← Back to Seasons</a>';
    $html .= '</div>';

    $html .= '<h2 style="margin:10px 0;">' . $show_title . ' — Season ' . $season_num . '</h2>';

    if (empty($episodes))
    {
        return $html . '<p>No episodes found.</p>';
    }

    /* Create the header row of the table */
    $html .= '<table style = "width : 100%; border-collapse : collapse;" >';
    $html .= '
            <thead><tr>
                <th 
                    class = "table-title-cells-style" >Ep
                </th>
                <th
                    class = "table-title-cells-style" >Title
                </th>
                <th
                    class = "table-title-cells-style" >Air Date
                </th>
                <th
                    class = "table-title-cells-style" >Runtime
                </th>
                <th
                    class = "table-title-cells-style" >' . esc_html($cfg['labelA']) . ' Rating
                </th>
                <th
                    class = "table-title-cells-style" >' . esc_html($cfg['labelB']) . ' Rating
                </th>
            </tr></thead><tbody>';

    /* Now, create a row for each episode of the selected TV show for the current couple */
    foreach ($episodes as $ep) 
    {
        /* Get all the relevant information we want about the current episode */
        $ep_id    = intval($ep['id'] ?? 0); 
        $ep_num   = esc_html($ep['episode_number'] ?? '');
        $ep_title = esc_html($ep['episode_title'] ?? '');
        $air      = esc_html($ep['air_date'] ?? '');
        $rt       = esc_html($ep['episode_runtime'] ?? '');

        /* Grab the actual review array for this episode */
        $reviews = $ep['reviews'] ?? [];

        /* Get all of the information for both users of the current couple's ratings for this episode */
        $u1 = review_value($reviews, $cfg['a'], 'rating');
        $u2 = review_value($reviews, $cfg['b'], 'rating');
        $c1 = color_rating_cell($u1);
        $c2 = color_rating_cell($u2);

        /* Get all of the information for both users of the current couple's reviews for this episode */
        $u1_review = review_value($reviews, $cfg['a'], 'review');
        $u2_review = review_value($reviews, $cfg['b'], 'review');
        $u1_id     = review_value($reviews, $cfg['a'], 'id');
        $u2_id     = review_value($reviews, $cfg['b'], 'id');

        $u1_reviewer = strtolower($cfg['a']);
        $u2_reviewer = strtolower($cfg['b']);

        $ep_title_for_modal = $show_title_raw . ' — S' . ($season['season_number'] ?? '') . 'E' . ($ep['episode_number'] ?? '') . ' — ' . ($ep['episode_title'] ?? '');

        /* Actually create the row here */
        $html .= 
                '<tr>
                    <td class = "tables-small-data-style" >' . $ep_num . '</td>
                    <td class = "tables-small-data-style" >' . $ep_title . '</td>
                    <td class = "tables-small-data-style" >' . $air . '</td>
                    <td class = "tables-small-data-style ">' . $rt . '</td>

                    <td 
                        class = "rating-cell tv-rating-cell tables-small-data-style"
                        data-review-type   = "tv"
                        data-target-type   = "episode"
                        data-couple-slug   = "' . esc_attr($couple) . '"
                        data-reviewer      = "' . esc_attr($u1_reviewer) . '"
                        data-id            = "' . esc_attr($ep_id) . '"
                        data-tv-show-title = "' . esc_attr($ep_title_for_modal) . '"
                        data-review-id     = "' . esc_attr($u1_id) . '"
                        data-rating        = "' . esc_attr($u1) . '"
                        data-review        = "' . esc_attr($u1_review) . '"
                        style = "background-color:' . esc_attr($c1) . ';">' . esc_html($u1) . 
                    '</td>

                    <td 
                        class = "rating-cell tv-rating-cell tables-small-data-style"
                        data-review-type   = "tv"
                        data-target-type   = "episode"
                        data-couple-slug   = "' . esc_attr($couple) . '"
                        data-reviewer      = "' . esc_attr($u2_reviewer) . '"
                        data-id            = "' . esc_attr($ep_id) . '"
                        data-tv-show-title = "' . esc_attr($ep_title_for_modal) . '"
                        data-review-id     = "' . esc_attr($u2_id) . '"
                        data-rating        = "' . esc_attr($u2) . '"
                        data-review        = "' . esc_attr($u2_review) . '"
                        style = "background-color:' . esc_attr($c2) . ';">' . esc_html($u2) . 
                    '</td>
                </tr>';
    }

    $html .= '</tbody></table>';
    return $html;
});