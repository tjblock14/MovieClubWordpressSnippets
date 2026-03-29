<?php\
add_shortcode('tv_seasons_view', function()
{
    $couple  = sanitize_text_field($_GET['couple'] ?? '');
    $show_id = intval($_GET['show_id'] ?? 0);

    $cfg = tv_couple_config($couple);
    if (!$cfg)
    {
        return '<p>Error: Invalid couple.</p>';
    }

    if (!$show_id)
    {
        return '<p>Error: Missing show_id.</p>';
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

    // Find the selected show
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

    $show_title_raw = $show['title'] ?? '';
    $show_title     = esc_html($show_title_raw);
    $seasons        = $show['seasons'] ?? [];

    // Back to Shows (simple)
    $return_to = isset($_GET['return_to']) ? rawurldecode($_GET['return_to']) : '';
    $back_url  = $return_to ?: home_url('/');

    // Episodes page
    $episodes_page = tv_page_url_by_slug('tv-show-season-episodes');

    $html  = '<div style = "margin : 10px 0;" >';
    $html .= '<a href = "' . esc_url($back_url) . '" > ← Back to Shows</a>';
    $html .= '</div>';

    // Page title (optional)
    // If you do NOT want "Stranger Things" to appear twice, keep this <h2> and the header below will NOT repeat it.
    $html .= '<h2 style = "margin : 10px 0; color : var(--default_table_text_color);" >' . $show_title . '</h2>';


    // =========================================================
    // SHOW DETAILS HEADER (poster + genres + cast + meta + blurb)
    // =========================================================

    // Helper: safely join arrays of strings/objects into "a, b, c"
    $join_names = function($arr)
    {
        if (!is_array($arr)) return '';
        $names = [];
        foreach ($arr as $item)
        {
            if(is_string($item))
            {
                $names[] = $item;
            }
            else if(is_array($item) && isset($item['name']))
            { 
                $names[] = $item['name'];
            }
        }

        $names = array_values(array_filter(array_map('trim', $names)));

        return implode(', ', $names);
    };

    /* Get all of the information about the show that we would like to display at the top of the page */
    $poster_url   = $show['poster_url']   ?? ($show['poster'] ?? ($show['image_url'] ?? ''));
    $show_summary = wp_kses_post($show['summary'] ?? ''); // remove surrounding <p>
    $genres_raw   = $show['genres']       ?? ($show['genre'] ?? []);
    $cast_raw     = $show['cast']         ?? ($show['actors'] ?? []);
    $creator_raw  = $show['created_by']   ?? ($show['creators'] ?? []);
    $network_raw  = $show['network']      ?? ($show['networks'] ?? '');
    $status_raw   = $show['status']       ?? '';
    $first_air    = $show['first_air_date'] ?? ($show['release_date'] ?? '');
    $last_air     = $show['last_air_date']  ?? '';
    $tmdb_id      = $show['tmdb_id']      ?? '';
    $tvmaze_id    = $show['tvmaze_id']    ?? '';
    $episode_run  = $show['episode_run_time'] ?? ($show['runtime'] ?? ''); // could be int or array

    // Normalize fields
    $genres_list = is_array($genres_raw) ? $genres_raw : (strlen($genres_raw) ? preg_split('/\s*,\s*/', $genres_raw) : []);
    $cast_list   = is_array($cast_raw) ? $cast_raw : (strlen($cast_raw) ? preg_split('/\s*,\s*/', $cast_raw) : []);

    $creators = is_array($creator_raw) ? $join_names($creator_raw) : (string)$creator_raw;
    $cast     = $join_names($cast_list);

    // runtime sometimes comes as [50] from APIs
    if (is_array($episode_run))
    {
        $episode_run = (count($episode_run) ? $episode_run[0] : '');
    }

    /* Turn all values that need it into the correct format for display */
    $poster_url_esc = esc_url($poster_url);
    $network        = esc_html(is_array($network_raw) ? $join_names($network_raw) : (string)$network_raw);
    $status         = esc_html((string)$status_raw);
    $first_air_esc  = esc_html((string)$first_air);
    $last_air_esc   = esc_html((string)$last_air);
    $creators_esc   = esc_html((string)$creators);
    $cast_esc       = esc_html((string)$cast);
    $runtime_esc    = esc_html((string)$episode_run);

    // Summary display (shorten if long)
    $show_summary_display = '';
    if (is_string($show_summary) && trim($show_summary) !== '')
    {
        $ov = trim($show_summary);

        if (mb_strlen($ov) > 420)
        {
            $ov = mb_substr($ov, 0, 420) . '…';
        }

        $show_summary_display = esc_html($ov);
    }

    // Header HTML
    $html .= '<div class = "TvShowHeader" >';

    // Poster (optional)
    if (!empty($poster_url_esc))
    {
        $html .= '
            <div
                class = "TvShowPoster" >
                <img src = "' . $poster_url_esc . '" 
                alt      = "' . esc_attr($show_title_raw) . ' poster" 
                loading  = "lazy" >
            </div>';
    }
    else
    {
        $html .= '
            <div 
                class = "TvShowPoster" 
                style = "display : flex; align-items : center; justify-content : center; height : 210px;" >
                    opacity : 0.75; font-size : 12px;" > No poster</span>
            </div>';
    }

    $html .= '<div class = "TvShowHeaderMain" >';

    // Genres as badges
    if (!empty($genres_list) && is_array($genres_list))
    {
        $html .= '<div class = "TvShowBadges">';
        foreach ($genres_list as $g)
        {
            $g = is_string($g) ? $g : ($g['name'] ?? '');
            $g = trim((string)$g);
            
            if($g === '')
            {
                continue;
            }
            $html .= '<span class = "TvShowBadge">' . esc_html($g) . '</span>';
        }
        $html .= '</div>';
    }

    // Key metadata grid
    $html .= '<div class = "TvShowMetaGrid">';

    if($first_air_esc !== '')
    {
        $html .= '<div class = "TvShowMetaItem"><b>First air:</b> ' . $first_air_esc . '</div>';
    }

    if($last_air_esc !== '')
    {
        $html .= '<div class = "TvShowMetaItem"><b>Last air:</b> ' . $last_air_esc . '</div>';
    }

    if($network !== '')
    {
        $html .= '<div class = "TvShowMetaItem"><b>Network:</b> ' . $network . '</div>';
    }

    if($status !== '')
    {
        $html .= '<div class = "TvShowMetaItem"><b>Status:</b> ' . $status . '</div>';
    }

    if($runtime_esc !== '')
    {
        $html .= '<div class = "TvShowMetaItem"><b>Runtime:</b> ' . $runtime_esc . ' min</div>';
    }

    $season_count = is_array($seasons) ? count($seasons) : 0;
    if($season_count > 0)
    {
        $html .= '<div class = "TvShowMetaItem"><b>Seasons:</b> ' . esc_html($season_count) . '</div>';
    }

    if($creators_esc !== '')
    {
        $html .= '<div class = "TvShowMetaItem"><b>Creators:</b> ' . $creators_esc . '</div>';
    }

    if($cast_esc !== '')
    {
        $html .= '<div class = "TvShowMetaItem"><b>Cast:</b> ' . $cast_esc . '</div>';
    }

    $html .= '</div>'; // meta grid

    // Overview
    if ($show_summary_display !== '')
    {
        $html .= '<div class = "TvShowOverview"><b>Summary:</b> ' . $show_summary_display . '</div>';
    }

    $html .= '</div>'; // header main
    $html .= '</div>'; // header wrapper


    // =========================
    // SEASONS TABLE
    // =========================

    if (empty($seasons))
    {
        return $html . '<p>No seasons found.</p>';
    }

    /* Create the header row of the episode table */
    $html .= '<table style = "width : 100%; border-collapse : collapse;" >';
    $html .= '<thead><tr>
                <th class = "table-title-cells-style" >Season</th>
                <th class = "table-title-cells-style" >Episodes</th>
                <th class = "table-title-cells-style" >Release Year</th>
                <th class = "table-title-cells-style" >' . esc_html($cfg['labelA']) . ' Rating</th>
                <th class = "table-title-cells-style" >' . esc_html($cfg['labelB']) . ' Rating</th>
            </tr></thead><tbody>';

    /* Create a row for each season of the current Tv Show for the current couple */
    foreach ($seasons as $season)
    {
        $season_id  = intval($season['id'] ?? 0);
        $season_num = esc_html($season['season_number'] ?? '');
        $ep_cnt     = esc_html($season['season_episode_cnt'] ?? '');
        $yr         = esc_html($season['season_release_year'] ?? '');

        $season_reviews = $season['reviews'] ?? [];

        // Ratings + colors
        $u1 = review_value($season_reviews, $cfg['a'], 'rating');
        $u2 = review_value($season_reviews, $cfg['b'], 'rating');
        $c1 = color_rating_cell($u1);
        $c2 = color_rating_cell($u2);

        // Review text + review IDs (for modal/edit JS)
        $u1_review = review_value($season_reviews, $cfg['a'], 'review');
        $u2_review = review_value($season_reviews, $cfg['b'], 'review');
        $u1_id     = review_value($season_reviews, $cfg['a'], 'id');
        $u2_id     = review_value($season_reviews, $cfg['b'], 'id');

        $u1_reviewer = strtolower($cfg['a']);
        $u2_reviewer = strtolower($cfg['b']);

        // Build episodes URL
        $episodes_url = tv_build_url($episodes_page, [
            'couple'    => $couple,
            'show_id'   => $show_id,
            'season_id' => $season_id,
            'return_to' => rawurlencode($back_url),
            ]);

        // Title for modal header
        $season_title_for_modal = $show_title_raw . ' — Season ' . ($season['season_number'] ?? '');

        /* Now, create the row for the current episode */
        $html .= '<tr class="season-link-row" data-episodes-url="' . esc_url($episodes_url) . '" style="cursor:pointer;">
                    <td class = "tables-small-data-style">S' . $season_num . '</td>
                    <td class = "tables-small-data-style">' . $ep_cnt . '</td>
                    <td class = "tables-small-data-style">' . $yr . '</td>

                    <td class = "rating-cell tv-rating-cell tables-small-data-style"
                    data-review-type   = "tv"
                    data-target-type   = "season"
                    data-couple-slug   = "' . esc_attr($couple) . '"
                    data-reviewer      = "' . esc_attr($u1_reviewer) . '"
                    data-id            = "' . esc_attr($season_id) . '"
                    data-tv-show-title = "' . esc_attr($season_title_for_modal) . '"
                    data-review-id     = "' . esc_attr($u1_id) . '"
                    data-rating        = "' . esc_attr($u1) . '"
                    data-review        = "' . esc_attr($u1_review) . '"
                    style = "background-color:' . esc_attr($c1) . ';">' . esc_html($u1) . '</td>

                    <td class = "rating-cell tv-rating-cell tables-small-data-style"
                    data-review-type   = "tv"
                    data-target-type   = "season"
                    data-couple-slug   = "' . esc_attr($couple) . '"
                    data-reviewer      = "' . esc_attr($u2_reviewer) . '"
                    data-id            = "' . esc_attr($season_id) . '"
                    data-tv-show-title = "' . esc_attr($season_title_for_modal) . '"
                    data-review-id     = "' . esc_attr($u2_id) . '"
                    data-rating        = "' . esc_attr($u2) . '"
                    data-review        = "' . esc_attr($u2_review) . '"
                    style = "background-color:' . esc_attr($c2) . ';">' . esc_html($u2) . '</td>
                </tr>';
    }

    $html .= '</tbody></table>';

    // Row navigation (DO NOT navigate when clicking rating cells or other interactive elements)
    $html .= '<script>
                (function() 
                {
                    document.addEventListener("click", function(e) 
                    {
                        const r = e.target.closest("tr.season-link-row");
                        if (!r) return;

                        // Do not navigate if clicking interactive elements
                        if(   (e.target.closest(".rating-cell"))
                        || (e.target.closest("a")) 
                        || (e.target.closest("button"))
                        || (e.target.closest("input")) 
                        || (e.target.closest("textarea"))
                        || (e.target.closest("select"))) 
                        {
                            return;
                        }

                        const url = r.dataset.episodesUrl;
                        if(url) 
                        {
                            window.location.href = url;
                        }
                    });
                })();
            </script>';

    return $html;
});
