<?php
/*******************************************************
 * New TV Show Main Page
 * - Fetches all TV Shows from the Django API.
 * - Displays posters in a responsive grid.
 * - Hover overlay shows movie title + club average
 *       for the show as a whole.
 * - Clicking a poster goes to the TV Show detail page.
 *******************************************************/

if (!function_exists('hex_to_rgba'))
{
    function hex_to_rgba($hex, $alpha)
    {
        $hex = str_replace('#', '', $hex);

        if (strlen($hex) === 3)
        {
            $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
            $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
            $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
        }
        else
        {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        return "rgba($r, $g, $b, $alpha)";
    }
}

if (!function_exists('mc_new_TvShow_home_page_shortcode'))
{
    function mc_new_TvShow_home_page_shortcode($atts = [])
    {
        $api_url = 'https://movieclubdatabase.onrender.com/api/shows/';

        $response = wp_remote_get($api_url, [
            'timeout' => 20,
        ]);

        if (is_wp_error($response))
        {
            return '<div class="mc-movie-grid-error">Could not load Tv Shows.</div>';
        }

        $body   = wp_remote_retrieve_body($response);
        $TvShows = json_decode($body, true);

        if (!is_array($TvShows) || empty($TvShows))
        {
            return '<div class="mc-movie-grid-empty">No Tv Shows found.</div>';
        }

        $html = '';
        $html .= '<div class="mc-movie-home-grid">';

        foreach ($TvShows as $TvShow)
        {
            $TvShow_id = isset($TvShow['id']) ? intval($TvShow['id']) : 0;

            if (!$TvShow_id)
            {
                continue;
            }

            $title        = isset($TvShow['title']) ? $TvShow['title'] : 'Untitled';
            $poster_url   = !empty($TvShow['image_url']) ? $TvShow['image_url'] : '';

            $club_average_rating      = isset($TvShow['club_average_rating']) ? (float)$TvShow['club_average_rating'] : null;
            $count    = (int)($TvShow['number_of_reviews'] ?? 0);

            $club_avg_color = function_exists('color_rating_cell') ? color_rating_cell($club_average_rating) : '';

            /* Convert the color to rgba qwith 0.6 opacity so any overlay text can have its own opacity */
            $overlay_color = hex_to_rgba($club_avg_color, 0.6);

            $detail_url = site_url('/TvShow-details/?tvshowid=' . $TvShow_id);

            $html .= '<a class="mc-movie-home-card" href="' . esc_url($detail_url) . '">';
                $html .= '<div class="mc-movie-home-poster-wrap">';

                    if (!empty($poster_url))
                    {
                        $html .= '<img 
                                    class="mc-movie-home-poster" 
                                    src="' . esc_url($poster_url) . '" 
                                    alt="' . esc_attr($title) . '"
                                  >';
                    }
                    else
                    {
                        $html .= '<div class="mc-movie-home-poster-placeholder">No Poster</div>';
                    }

                    $html .= '<div class = "mc-movie-home-overlay" style = "background-color : '.esc_attr($overlay_color) . ';" >';
                        $html .= '<div class = "mc-movie-home-overlay-content">';

                            if ($club_average_rating !== null)
                            {
                                $html .= '<div class = "mc-movie-home-rating">';
                                    $html .= esc_html(number_format($club_average_rating, 1));
                                $html .= '</div>';
                            }

                        $html .= '</div>';
                    $html .= '</div>';

                $html .= '</div>';
            $html .= '</a>';
        }

        $html .= '</div>';

        return $html;
    }

    add_shortcode('new_TvShow_home_page', 'mc_new_TvShow_home_page_shortcode');
}