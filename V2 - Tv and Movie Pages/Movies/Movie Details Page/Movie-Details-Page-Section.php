<?php
/*******************************************************
 * Movie Detail Page
 * - Reads movie_id from the URL
 * - Fetches all movies from the Django API
 * - Finds the selected movie
 * - Displays a top banner/header similar to TV show pages
 * - Leaves space for ratings/reviews sections underneath
 *******************************************************/

if (!function_exists('movie_detail_view'))
{
    add_shortcode('movie_detail_view', function()
    {
        $movie_id = intval($_GET['movie_id'] ?? 0);

        if (!$movie_id)
        {
            return '<p>Error: Missing movie_id.</p>';
        }

        $api_url = 'https://movieclubdatabase.onrender.com/api/movies/';

        $response = wp_remote_get($api_url, [
            'timeout' => 20,
        ]);

        if (is_wp_error($response))
        {
            return '<p>Error: Could not load movie data.</p>';
        }

        $body   = wp_remote_retrieve_body($response);
        $movies = json_decode($body, true);

        if (!is_array($movies))
        {
            return '<p>Error: No data returned.</p>';
        }

        $movie = null;
        foreach ($movies as $m)
        {
            if (intval($m['id'] ?? 0) === $movie_id)
            {
                $movie = $m;
                break;
            }
        }

        if (!$movie)
        {
            return '<p>Error: Movie not found.</p>';
        }

        // -----------------------------------------
        // Pull movie fields
        // -----------------------------------------
        $movie_title_raw = $movie['title'] ?? '';
        $movie_title     = esc_html($movie_title_raw);

        $poster_url   = $movie['poster_url'] ?? '';
        $summary_raw  = $movie['summary'] ?? '';
        $director_raw = $movie['director'] ?? [];
        $actors_raw   = $movie['actors'] ?? [];
        $genres_raw   = $movie['genres'] ?? [];
        $release_yr   = $movie['release_yr'] ?? '';
        $runtime      = $movie['runtime'] ?? '';
        $club_average = $movie['club_average'] ?? '';

        // -----------------------------------------
        // Normalize arrays into strings
        // -----------------------------------------
        $director = is_array($director_raw) ? implode(', ', array_filter(array_map('trim', $director_raw))) : (string)$director_raw;
        $actors   = is_array($actors_raw)   ? implode(', ', array_filter(array_map('trim', $actors_raw)))   : (string)$actors_raw;
        $genres   = is_array($genres_raw)   ? $genres_raw : (strlen((string)$genres_raw) ? preg_split('/\s*,\s*/', (string)$genres_raw) : []);

        // -----------------------------------------
        // Escape for output
        // -----------------------------------------
        $poster_url_esc = esc_url($poster_url);
        $director_esc   = esc_html($director);
        $actors_esc     = esc_html($actors);
        $release_yr_esc = esc_html((string)$release_yr);
        $runtime_esc    = esc_html((string)$runtime);

        $club_avg_display = '';
        if ($club_average !== '' && $club_average !== null)
        {
            $club_avg_display = esc_html(number_format((float)$club_average, 1));
        }

        // Summary
        $summary_display = '';
        if (is_string($summary_raw) && trim($summary_raw) !== '')
        {
            $summary_display = esc_html(trim($summary_raw));
        }

        // Back link
        $return_to = isset($_GET['return_to']) ? rawurldecode($_GET['return_to']) : '';
        $back_url  = $return_to ?: home_url('/movies/');

        // -----------------------------------------
        // Start HTML
        // -----------------------------------------
        $html  = '<div style = "margin : 10px 0;" >';
        $html .= '<a href = "' . esc_url($back_url) . '" >← Back to Movies</a>';
        $html .= '</div>';

        $html .= '<h2 style = "margin : 10px 0; color : var(--default_table_text_color);" >' . $movie_title . '</h2>';

        // =========================================
        // MOVIE DETAILS HEADER
        // =========================================
        $html .= '<div class = "MovieHeader" >';

        // Poster
        if (!empty($poster_url_esc))
        {
            $html .= '
                <div class = "MoviePoster" >
                    <img src = "' . $poster_url_esc . '"
                         alt = "' . esc_attr($movie_title_raw) . ' poster"
                         loading = "lazy" >
                </div>';
        }
        else
        {
            $html .= '
                <div class = "MoviePoster MoviePosterNoImage" >
                    No poster
                </div>';
        }

        $html .= '<div class = "MovieHeaderMain" >';

        // Genres as badges
        if (!empty($genres) && is_array($genres))
        {
            $html .= '<div class = "MovieBadges">';
            foreach ($genres as $g)
            {
                $g = trim((string)$g);
                if ($g === '')
                {
                    continue;
                }

                $html .= '<span class = "MovieBadge">' . esc_html($g) . '</span>';
            }
            $html .= '</div>';
        }

        // Metadata grid
        $html .= '<div class = "MovieMetaGrid">';

        if ($release_yr_esc !== '')
        {
            $html .= '<div class = "MovieMetaItem"><b>Release Year:</b> ' . $release_yr_esc . '</div>';
        }

        if ($runtime_esc !== '')
        {
            $html .= '<div class = "MovieMetaItem"><b>Runtime:</b> ' . $runtime_esc . ' min</div>';
        }

        if ($director_esc !== '')
        {
            $html .= '<div class = "MovieMetaItem"><b>Director:</b> ' . $director_esc . '</div>';
        }

        if ($actors_esc !== '')
        {
            $html .= '<div class = "MovieMetaItem"><b>Actors:</b> ' . $actors_esc . '</div>';
        }

        if ($club_avg_display !== '')
        {
            $html .= '<div class = "MovieMetaItem"><b>Club Average:</b> ' . $club_avg_display . '</div>';
        }

        $html .= '</div>'; // MovieMetaGrid

        if ($summary_display !== '')
        {
            $html .= '<div class = "MovieOverview"><b>Summary:</b> ' . $summary_display . '</div>';
        }

        $html .= '</div>'; // MovieHeaderMain
        $html .= '</div>'; // MovieHeader

        // =========================================
        // PLACEHOLDER SECTIONS BELOW HEADER
        // =========================================
        $html .= '<div class = "MovieDetailSection">';
        $html .= '<h3 class = "MovieDetailSectionTitle">Ratings & Reviews</h3>';
        $html .= '<p>This is where club average, couple averages, member ratings, and review submission can go next.</p>';
        $html .= '</div>';

        return $html;
    });
}