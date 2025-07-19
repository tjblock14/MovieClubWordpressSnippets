
// function to make sure the data is displayed in the table separated by commas, and not in an array [] like that
function safe_implode($field) {
    return is_array($field) ? implode(', ', $field) : $field;
}

// Shortcode function for the whole table
add_shortcode('tnt_table', function() {

    // ✅ Updated to call the correct endpoint (must match the WPGetAPI endpoint name in WP settings)
    $data = wpgetapi_endpoint('movie_club_database_api', 'tnt_reviews', array(
        'return' => 'body',
        'debug' => false,
        'cache' => false
    ));

    $data = json_decode($data, true);  // Convert JSON string to PHP array

    // ✅ Make sure the data includes a 'results' key, which holds the actual movie entries
    if (!is_array($data) || !isset($data['results'])) return '<p>Error: No data returned</p>';
    $movies = $data['results'];

    // Function to set background color for rating cells
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

        return '<td style="background-color: ' . $color . '; color: white; font-weight: bold; font-size: 14px; text-align: center; vertical-align: middle; width: 100px; border-collapse: collapse; border: 1px solid black;">' . esc_html($rating) . '</td>';
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
                <th style="width: 100px; font-size: 18px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">Trevor Rating</th>
                <th style="width: 600px; font-size: 18px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">Trevor Review</th>
                <th style="width: 100px; font-size: 18px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">Taylor Rating</th>
                <th style="width: 600px; font-size: 18px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">Taylor Review</th>
            </tr>
        </thead>
        <tbody>';

    // ✅ Updated to loop through the actual movie entries in 'results'
    foreach ($movies as $movie) {
		$movie_id = $movie['id'];
        $html .= '<tr>';

       $html .= '<td style="width: 200px; text-align: center; vertical-align: middle; font-size: 18px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">' . esc_html($movie['title']) . '</td>';

	   $html .= '<td style="width: 200px; text-align: center; vertical-align: middle; font-size: 14px; border-collapse: collapse; border: 1px solid black;">' . esc_html(safe_implode($movie['director'])) . '</td>';

	   $html .= '<td style="width: 250px; text-align: center; vertical-align: middle; font-size: 14px; border-collapse: collapse; border: 1px solid black;">' . esc_html(safe_implode($movie['actors'])) . '</td>';

	   $html .= '<td style="width: 200px; text-align: center; vertical-align: middle; font-size: 14px; font-weight: bold; border-collapse: collapse; border: 1px solid black;">' . esc_html(safe_implode($movie['genres'])) . '</td>';


        // ✅ Capitalized keys ("Trevor", "Taylor") match the JSON structure returned by the API
        $trevor_rating  = $movie['reviews']['Trevor']['rating'] ?? '';
        $trevor_review  = $movie['reviews']['Trevor']['review'] ?? '';
        $taylor_rating  = $movie['reviews']['Taylor']['rating'] ?? '';
        $taylor_review  = $movie['reviews']['Taylor']['review'] ?? '';

        $html .= '<td class="rating-cell" data-reviewer="Trevor" data-id="' . $movie_id . '" data-rating="' . esc_attr($trevor_rating) . '">' . color_rating_cell($trevor_rating) . '</td>';
        $html .= '<td class="review-cell" data-reviewer="Trevor" data-id="' . $movie_id . '">' . esc_html($trevor_review) . '</td>';

        $html .= '<td class="rating-cell" data-reviewer="taylor" data-id="' . $movie_id . '" data-rating="' . esc_attr($taylor_rating) . '">' . color_rating_cell($taylor_rating) . '</td>';
        $html .= '<td class="review-cell" data-reviewer="taylor" data-id="' . $movie_id . '">' . esc_html($taylor_review) . '</td>';

        $html .= '</tr>';
    }

    $html .= '</tbody></table></div>';

	/*
$html .= '
<script>

document.addEventListener("DOMContentLoaded", function () {
    const username = localStorage.getItem("username");
    const token = localStorage.getItem("access_token");

    if (!username || !token) return;

    document.querySelectorAll(".rating-cell").forEach(cell => {
        if (cell.dataset.reviewer === username) {
            const rating = cell.dataset.rating || "";
            const movieId = cell.dataset.id;

            cell.innerHTML =
              \'<input type="number" min="0" max="10" id="rating-\' + username + \'-\' + movieId + \'" value="\' + rating + \'" style="width: 80px; font-weight: bold; text-align: center;">\';
        }
    });

    document.querySelectorAll(".review-cell").forEach(cell => {
        if (cell.dataset.reviewer === username) {
            const movieId = cell.dataset.id;
            const currentReview = cell.innerText;

            cell.innerHTML =
              \'<textarea id="review-\' + username + \'-\' + movieId + \'" style="width: 100%; height: 60px;">\' +
              currentReview.replace(/"/g, \'&quot;\').replace(/</g, \'&lt;\') +
              \'</textarea>\' +
              \'<button onclick="updateReview(\' + movieId + \', \\\'\' + username + \'\\\')">Save</button>\';
        }
    });
});

function updateReview(movieId, reviewer) {
    const rating = document.getElementById("rating-" + reviewer + "-" + movieId)?.value;
    const review = document.getElementById("review-" + reviewer + "-" + movieId)?.value;
    const token = localStorage.getItem("access_token");
	
	const username = localStorage.getItem("username")
	const reviewUrl = get_url_by_couple(username);

    if (!token) {
        alert("You must be logged in to update a review.");
        return;
    }

    fetch(reviewUrl + movieId, {
        method: "PATCH",
        headers: {
            "Content-Type": "application/json",
            "Authorization": "Bearer " + token
        },
        body: JSON.stringify({
            rating: parseFloat(rating),
            review_text: review,
            reviewer: reviewer
        })
    })
    .then(res => {
        if (!res.ok) throw new Error("Failed to update");
        return res.json();
    })
    .then(data => {
        alert("Review updated!");
        location.reload();
    })
    .catch(err => {
        alert("Error: " + err.message);
    });
}

function get_url_by_couple(username)
{
	if((username === "trevor") ||(username === "taylor"))
	{
		return "https://movieclubdatabase.onrender.com/api/couple_reviews/tt/";
	}
	else if((username === "marissa") ||(username === "nathan"))
	{
		return "https://movieclubdatabase.onrender.com/api/couple_reviews/mn/";
	}
	else if((username === "sierra") ||(username === "benett"))
	{
		return "https://movieclubdatabase.onrender.com/api/couple_reviews/sb/";
	}
	else
	{
		return null;
	}
}
</script>';

*/
    return $html;
});
