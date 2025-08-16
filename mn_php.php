<?php
// function to make sure the data is displayed in the table separated by commas, and not in an array [] like that
if(!function_exists('safe_implode')) 
{
	function safe_implode($field) 
	{
    	return is_array($field) ? implode(', ', $field) : $field;
	}	
}

// Shortcode function for the whol	e table
add_shortcode('mn_table', function() {

    // Updated to call the correct endpoint (must match the WPGetAPI endpoint name in WP settings)
    $data = wpgetapi_endpoint('movie_club_database_api', 'mn_reviews', array(
        'return' => 'body',
        'debug' => false,
        'cache' => false
    ));

    $data = json_decode($data, true);  // Convert JSON string to PHP array

    // Make sure the data includes a 'results' key, which holds the actual movie entries
    if (!is_array($data) || !isset($data['results'])) return '<p>Error: No data returned</p>';
    $movies = $data['results'];

    // Function to set background color for rating cells
    if (!function_exists('color_rating_cell'))
	{
		function color_rating_cell($rating)
		{
			if (!is_numeric($rating)) return '<td></td>';

			$color = '';
			if ($rating >= 9.0) 
			{
				$color = "#3C8D40"; // dark green
			} 
			else if($rating < 9.0 && $rating >= 7.5)
			{
				 $color = "#5F9F61"; // light green
			}
			else if($rating < 7.5 && $rating >= 6.0)
			{
				$color = "#A6CDA8"; // very light green
			}
			else if($rating < 6.0 && $rating >= 5.0)
			{
				$color = "#F3EAA3"; // yellow
			}
			else if($rating < 5.0 && $rating >= 3.5)
			{
				$color = "#EFB45D"; // orange
			}
			else if($rating < 3.5 && $rating > 1)
			{
				$color = "#D7572E"; // light red
			}
			else if($rating <= 1.0) {
				$color = "#AC2727";  // dark red
			}

			return $color;
		}
	}

    // Begin scrollable table wrapper
    $html = '<div style="overflow-x: auto;">
   <table style="border-collapse: collapse; min-width: 1400px; font-size: 14px; border: 2px solid black;">
        <thead>
            <tr>
                <th style="width: 150px; text-align: center; font-size: 18px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">Title</th>
                <th style="width: 200px; text-align: center; font-size: 18px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">Director</th>
                <th style="width: 250px; text-align: center; font-size: 18px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">Actors</th>
                <th style="width: 200px; text-align: center; font-size: 18px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">Genres</th>
                <th style="width: 100px; font-size: 18px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">Marissa Rating</th>
                <th style="width: 600px; font-size: 18px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">Marissa Review</th>
                <th style="width: 100px; font-size: 18px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">Nathan Rating</th>
                <th style="width: 600px; font-size: 18px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">Nathan Review</th>
            </tr>
        </thead>
        <tbody>';

    // Updated to loop through the actual movie entries in 'results'
    foreach ($movies as $movie) {
        $movie_id = $movie['movie_id'];
        $html .= '<tr>';

        $html .= '<td style="width: 200px; text-align: center; vertical-align: middle; font-size: 18px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">' . esc_html($movie['title']) . '</td>';

        $html .= '<td style="width: 200px; text-align: center; vertical-align: middle; font-size: 14px; border-collapse: collapse; border: 1px solid black;">' . esc_html(safe_implode($movie['director'])) . '</td>';

        $html .= '<td style="width: 250px; text-align: center; vertical-align: middle; font-size: 14px; border-collapse: collapse; border: 1px solid black;">' . esc_html(safe_implode($movie['actors'])) . '</td>';

        $html .= '<td style="width: 200px; text-align: center; vertical-align: middle; font-size: 14px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">' . esc_html(safe_implode($movie['genres'])) . '</td>';

        // Capitalized keys ("Marissa", "Nathan") match the JSON structure returned by the API
        $marissa_rating  = $movie['reviews']['Marissa']['rating'] ?? '';
        $marissa_review  = $movie['reviews']['Marissa']['review'] ?? '';
        $nathan_rating  = $movie['reviews']['Nathan']['rating'] ?? '';
        $nathan_review  = $movie['reviews']['Nathan']['review'] ?? '';

        // Get the ID of the review so we have it when a POST or PATCH is sent to the API
        $review_id_marissa = $movie['reviews']['marissa']['id'] ?? '';
        $review_id_nathan = $movie['reviews']['nathan']['id'] ?? '';

        $marissa_color = color_rating_cell($marissa_rating);
        $html .= '<td class="rating-cell" data-reviewer="marissa" data-id="' . $movie_id . '" data-movie-title="' . esc_attr($movie['title']) . '" data-movie-title="' . esc_attr($movie['title']) . '" data-review-id="' . $review_id_marissa . '" data-rating="' . esc_attr($marissa_rating) . '" style="background-color: ' 
                . $marissa_color . ';">' . esc_html($marissa_rating) . '</td>';

        $html .= '<td class="review-cell" data-reviewer="marissa" data-id="' . $movie_id . '" data-movie-title="' . esc_attr($movie['title']) . '" data-movie-title="' . esc_attr($movie['title']) . '" data-review-id="' . $review_id_marissa . '">' . esc_html($marissa_review) . '</td>';


        $nathan_color = color_rating_cell($nathan_rating);
        $html .= '<td class="rating-cell" data-reviewer="nathan" data-id="' . $movie_id . '" data-movie-title="' . esc_attr($movie['title']) . '" data-movie-title="' . esc_attr($movie['title']) . '" data-review-id="' . $review_id_nathan . '" data-rating="' . esc_attr($nathan_rating) . '" style="background-color: ' 
                . $nathan_color . ';">' . esc_html($nathan_rating) . '</td>';

        $html .= '<td class="review-cell" data-reviewer="nathan" data-id="' . $movie_id . '" data-movie-title="' . esc_attr($movie['title']) . '" data-movie-title="' . esc_attr($movie['title']) . '" data-review-id="' . $review_id_nathan .'">' . esc_html($nathan_review) . '</td>';

        $html .= '</tr>';
    }

    $html .= '</tbody></table></div>';

    return $html;
});