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
if (!function_exists('add_movie_table_shortcode')) {
    function add_movie_table_shortcode(string $shortcode, string $endpoint, string $reviewerA, string $reviewerB, ?string $labelA = null, ?string $labelB = null) {

        add_shortcode($shortcode, function() use ($endpoint, $reviewerA, $reviewerB, $labelA, $labelB) {

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
            $movies = $data['results'];

            // Normalize labels for headers (allow custom labels like "Dad"/"Mom")
            $displayA = $labelA ?? $reviewerA;
            $displayB = $labelB ?? $reviewerB;

            // Build table
            $html = '<div style="overflow-x: auto;">
   <table style="border-collapse: collapse; min-width: 1400px; font-size: 14px; border: 2px solid black;">
        <thead>
            <tr>
                <th style="width: 150px; text-align: center; font-size: 18px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">Title</th>
                <th style="width: 200px; text-align: center; font-size: 18px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">Director</th>
                <th style="width: 250px; text-align: center; font-size: 18px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">Actors</th>
                <th style="width: 200px; text-align: center; font-size: 18px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">Genres</th>
                <th style="width: 100px; font-size: 18px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">' . esc_html($displayA) . ' Rating</th>
                <th style="width: 600px; font-size: 18px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">' . esc_html($displayA) . ' Review</th>
                <th style="width: 100px; font-size: 18px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">' . esc_html($displayB) . ' Rating</th>
                <th style="width: 600px; font-size: 18px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">' . esc_html($displayB) . ' Review</th>
            </tr>
        </thead>
        <tbody>';

            foreach ($movies as $movie) {
                $movie_id = $movie['movie_id'];
                $title    = $movie['title'] ?? '';

                $html .= '<tr>';

                // Title / Director / Actors / Genres
                $html .= '<td style="width: 200px; text-align: center; vertical-align: middle; font-size: 18px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">' . esc_html($title) . '</td>';
                $html .= '<td style="width: 200px; text-align: center; vertical-align: middle; font-size: 14px; border-collapse: collapse; border: 1px solid black;">' . esc_html(safe_implode($movie['director'] ?? '')) . '</td>';
                $html .= '<td style="width: 250px; text-align: center; vertical-align: middle; font-size: 14px; border-collapse: collapse; border: 1px solid black;">' . esc_html(safe_implode($movie['actors'] ?? '')) . '</td>';
                $html .= '<td style="width: 200px; text-align: center; vertical-align: middle; font-size: 14px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">' . esc_html(safe_implode($movie['genres'] ?? '')) . '</td>';

                // Reviews block
                $reviews = $movie['reviews'] ?? [];

                // Reviewer A values
                $a_rating = review_value($reviews, $reviewerA, 'rating');
                $a_review = review_value($reviews, $reviewerA, 'review');
                $a_id     = review_value($reviews, $reviewerA, 'id'); // allow for lowercase-only id keys
                $a_color  = color_rating_cell($a_rating);
                $a_data_reviewer = strtolower($reviewerA); // match your JS which uses lowercase usernames

                $html .= '<td class="rating-cell" data-reviewer="' . esc_attr($a_data_reviewer) . '" data-id="' . esc_attr($movie_id) . '" data-movie-title="' . esc_attr($title) . '" data-review-id="' . esc_attr($a_id) . '" data-rating="' . esc_attr($a_rating) . '" style="background-color: ' . esc_attr($a_color) . ';">' . esc_html($a_rating) . '</td>';

                $html .= '<td class="review-cell" data-reviewer="' . esc_attr($a_data_reviewer) . '" data-id="' . esc_attr($movie_id) . '" data-movie-title="' . esc_attr($title) . '" data-review-id="' . esc_attr($a_id) . '">' . esc_html($a_review) . '</td>';

                // Reviewer B values
                $b_rating = review_value($reviews, $reviewerB, 'rating');
                $b_review = review_value($reviews, $reviewerB, 'review');
                $b_id     = review_value($reviews, $reviewerB, 'id');
                $b_color  = color_rating_cell($b_rating);
                $b_data_reviewer = strtolower($reviewerB);

                $html .= '<td class="rating-cell" data-reviewer="' . esc_attr($b_data_reviewer) . '" data-id="' . esc_attr($movie_id) . '" data-movie-title="' . esc_attr($title) . '" data-review-id="' . esc_attr($b_id) . '" data-rating="' . esc_attr($b_rating) . '" style="background-color: ' . esc_attr($b_color) . ';">' . esc_html($b_rating) . '</td>';

                $html .= '<td class="review-cell" data-reviewer="' . esc_attr($b_data_reviewer) . '" data-id="' . esc_attr($movie_id) . '" data-movie-title="' . esc_attr($title) . '" data-review-id="' . esc_attr($b_id) . '">' . esc_html($b_review) . '</td>';

                $html .= '</tr>';
            }

            $html .= '</tbody></table></div>';

            return $html;
        });
    }
}

/*******************************************************
 * Register your four tables using the single function.
 * If you ever add a new couple, just add one new line.
 *******************************************************/

// Trevor / Taylor (labels match names)
add_movie_table_shortcode('tnt_table', 'tnt_reviews', 'Trevor', 'Taylor');

// Marissa / Nathan (labels match names)
add_movie_table_shortcode('mn_table', 'mn_reviews', 'Marissa', 'Nathan');

// Sierra / Benett (labels match names)
add_movie_table_shortcode('sb_table', 'sb_reviews', 'Sierra', 'Benett');

// Dad (Rob) / Mom (Terry): custom display labels different from API reviewer keys
add_movie_table_shortcode('mom_dad_table', 'mom_dad_reviews', 'Rob', 'Terry', 'Dad', 'Mom');
