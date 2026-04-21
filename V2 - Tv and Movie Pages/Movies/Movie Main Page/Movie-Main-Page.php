<?php
/*******************************************************
 * New Movie Home Page
 * - Fetches all movies from the Django API
 * - Displays posters in a responsive grid
 * - Hover overlay shows movie title + club average
 * - Clicking a poster goes to the movie detail page
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

if (!function_exists('mc_new_movie_home_page_shortcode'))
{
    function mc_new_movie_home_page_shortcode($atts = [])
    {
        $api_url = 'https://movieclubdatabase.onrender.com/api/movies/';

        $response = wp_remote_get($api_url, [
            'timeout' => 20,
        ]);

        if (is_wp_error($response))
        {
            return '<div class="mc-movie-grid-error">Could not load movies.</div>';
        }

        $body   = wp_remote_retrieve_body($response);
        $movies = json_decode($body, true);

        if (!is_array($movies) || empty($movies))
        {
            return '<div class="mc-movie-grid-empty">No movies found.</div>';
        }

        $html = '';
        $html .= '<div class="mc-movie-home-grid">';

        foreach ($movies as $movie)
        {
            $movie_id = isset($movie['id']) ? intval($movie['id']) : 0;

            if (!$movie_id)
            {
                continue;
            }

            $title        = isset($movie['title']) ? $movie['title'] : 'Untitled';
            $poster_url   = !empty($movie['poster_url']) ? $movie['poster_url'] : '';

            $club_average_rating      = isset($movie['club_average_rating']) ? (float)$movie['club_average_rating'] : null;
            $count    = (int)($movie['number_of_reviews'] ?? 0);

            $club_avg_color = function_exists('color_rating_cell') ? color_rating_cell($club_average_rating) : '';

            /* Convert the color to rgba qwith 0.6 opacity so any overlay text can have its own opacity */
            $overlay_color = hex_to_rgba($club_avg_color, 0.6);

            $detail_url = site_url('/movie-details/?movie_id=' . $movie_id);

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

    add_shortcode('new_movie_home_page', 'mc_new_movie_home_page_shortcode');
}