<?php
/*******************************************************
 * Club Average table (homepage)
 * Columns: Title | Director | Actors | Genres | Club Average | # Reviews
 *******************************************************/
if (!function_exists('club_safe_implode')) {
    function club_safe_implode($field) {
        return is_array($field) ? implode(', ', $field) : ($field ?? '');
    }
}

add_shortcode('club_average_table', function() {

    // Call your DRF averages endpoint configured in WPGetAPI as 'club_average'
    $resp = wpgetapi_endpoint('movie_club_database_api', 'club_average', [
        'return' => 'body',
        'debug'  => false,
        'cache'  => false,
        'query'  => ['_' => time()], // cache-buster
    ]);

    $data = json_decode($resp, true);
    if (!is_array($data) || !isset($data['results']) || !is_array($data['results'])) {
        return '<p>Error: No data returned</p>';
    }
    $rows = $data['results'];

    // Sort bar (client-side)
    $html = '
  <div class="movie-sortbar" style="margin:8px 0; display:flex; gap:8px; align-items:center;">
    <label for="club-sort" style="font-weight:600;">Sort by:</label>
    <select id="club-sort" class="club-sort">
      <option value="avg_desc">Club Average — high → low</option>
      <option value="avg_asc">Club Average — low → high</option>
      <option value="count_desc"># Reviews — high → low</option>
      <option value="count_asc"># Reviews — low → high</option>
      <option value="title_az">Title — A → Z</option>
      <option value="director_az">Director — A → Z</option>
    </select>
  </div>';

    // Table
    $html .= '<div style="overflow-x:auto;">
  <table id="club-avg-table" style="border-collapse:collapse; min-width:1100px; font-size:14px; border:2px solid black;">
    <thead>
      <tr>
        <th style="width:200px; text-align:center; font-size:18px; font-weight:bold; border:1px solid black;">Title</th>
        <th style="width:200px; text-align:center; font-size:18px; font-weight:bold; border:1px solid black;">Director</th>
        <th style="width:250px; text-align:center; font-size:18px; font-weight:bold; border:1px solid black;">Actors</th>
        <th style="width:200px; text-align:center; font-size:18px; font-weight:bold; border:1px solid black;">Genres</th>
        <th style="width:120px; text-align:center; font-size:18px; font-weight:bold; border:1px solid black;">Club Average</th>
        <th style="width:120px; text-align:center; font-size:18px; font-weight:bold; border:1px solid black;"># Reviews</th>
      </tr>
    </thead>
    <tbody>';

    foreach ($rows as $r) {
        $title    = $r['title'] ?? '';
        // Your backend might send 'director' or 'directors' (JSONField). Normalize:
        $director = club_safe_implode($r['director'] ?? ($r['directors'] ?? ''));
        $actors   = club_safe_implode($r['actors'] ?? '');
        $genres   = club_safe_implode($r['genres'] ?? '');

        $avg      = isset($r['avg_rating']) ? (float)$r['avg_rating'] : null;
        $count    = (int)($r['num_reviews'] ?? 0);

        $avg_color = function_exists('color_rating_cell') ? color_rating_cell($avg) : '';

        // Data attributes for accurate sorting
        $html .= '<tr
          data-sort-title="'   . esc_attr(strtolower($title))    . '"
          data-sort-director="' . esc_attr(strtolower($director)) . '"
          data-sort-avg="'     . esc_attr($avg !== null ? $avg : -1) . '"
          data-sort-count="'   . esc_attr($count) . '">';

        $html .= '<td class="title-cell"    style="border:1px solid black; padding:6px; text-align:center; font-weight:bold;">' . esc_html($title)    . '</td>';
        $html .= '<td class="director-cell" style="border:1px solid black; padding:6px; text-align:center;">'                  . esc_html($director) . '</td>';
        $html .= '<td                         style="border:1px solid black; padding:6px; text-align:center;">'                  . esc_html($actors)   . '</td>';
        $html .= '<td                         style="border:1px solid black; padding:6px; text-align:center; font-weight:bold;">' . esc_html($genres)   . '</td>';

        $html .= '<td class="avg-cell" data-rating="' . esc_attr($avg !== null ? $avg : '') . '" style="border:1px solid black; padding:6px; text-align:center; background-color:' . esc_attr($avg_color) . ';">'
              .     esc_html($avg !== null ? number_format($avg, 2) : '') . '</td>';

        $html .= '<td style="border:1px solid black; padding:6px; text-align:center;">' . esc_html($count) . '</td>';

        $html .= '</tr>';
    }

    $html .= '</tbody></table></div>';

    // Sorting script (simple + stable)
    $html .= '<script>
(function(){
  function asNumber(v){ if(v===null||v===undefined) return NaN; const n=parseFloat(String(v).replace(",", ".")); return Number.isFinite(n)?n:NaN; }
  function key(row, mode){
    switch(mode){
      case "avg_desc":
      case "avg_asc":   return asNumber(row.getAttribute("data-sort-avg"));
      case "count_desc":
      case "count_asc": return asNumber(row.getAttribute("data-sort-count"));
      case "title_az":  return row.getAttribute("data-sort-title")   || "";
      case "director_az": return row.getAttribute("data-sort-director") || "";
      default: return "";
    }
  }
  function compare(a,b,isNum,asc){
    if(isNum){ const aN=Number.isNaN(a), bN=Number.isNaN(b); if(aN&&bN) return 0; if(aN) return 1; if(bN) return -1; return asc? a-b : b-a; }
    if(a===b) return 0; return asc ? (a<b?-1:1) : (a>b?-1:1);
  }
  function sortTable(mode){
    const t=document.getElementById("club-avg-table"); if(!t) return;
    const tb=t.querySelector("tbody")||t;
    const rows=Array.from(tb.querySelectorAll("tr"));
    const asc=mode.endsWith("_asc");
    const numeric=new Set(["avg_desc","avg_asc","count_desc","count_asc"]).has(mode);
    const indexed=rows.map((row,i)=>({row,i,key:key(row,mode)}));
    indexed.sort((A,B)=>{ const c=compare(A.key,B.key,numeric,asc); return c!==0?c:(A.i-B.i); });
    indexed.forEach(x=>tb.appendChild(x.row));
  }
  document.addEventListener("DOMContentLoaded", function(){
    const sel=document.getElementById("club-sort"); if(!sel) return;
    sel.addEventListener("change", function(){ sortTable(this.value); });
    // sortTable(sel.value); // enable if you want default sort on load
  });
})();
</script>';

    return $html;
});
