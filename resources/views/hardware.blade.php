<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hardware</title>
    <!-- Linking to external CSS files -->
    <link rel="stylesheet" href= "css/hardware.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

</head>
<body>
<!-- Link to go back to select page -->
<a href="/select" class="dropdown-item">Search Hardware</a>
<div class="scrollable-content">
    <div class="container">
    <h1>Find {{$hardware_type}} Hardware</h1>
    <p>Enter a search term in the input box below to find {{$hardware_type}} hardware matching your requirements.</p>

    <form action="{{ route('hardware') }}" method="GET">
        @csrf
        <input type="text" name="search_term" id="search_term" placeholder="Search hardware...">
        <button type="submit">Search</button>
    </form>

    @if (empty($groupedInputMatch))
        <p>No hardware found</p>
    @else
        <ul>
        {{$count}} hardware found <br><br>
        <!-- Looping through grouped input matches -->
        @foreach ($groupedInputMatch as $key => $group)
            <button class="accordion"><h2><strong>{{ $key }}:</strong></h2>
            <span><i class="bi bi-caret-down-fill"></i></span></button>
            <div class="panel">
            <ul>
                <!-- Looping through hardware details -->
                @foreach ($group['rows'] as $row)
                    <!-- Displaying hardware details and occurrences -->
                    <li>{{ $row }} <span>({{ $group['occurrences'][$row] }})</span></li> 
                @endforeach
            </ul>
            </div>
        @endforeach
        </ul>
    @endif
    </div>
</div>
<script>
    var accordions = document.getElementsByClassName("accordion");
    var i;

    // Loop through all accordion elements
    for (i = 0; i < accordions.length; i++) {
        // Add click event listener to toggle accordion
        accordions[i].addEventListener("click", function() {
            this.classList.toggle("active");
            var panel = this.nextElementSibling;
            if (panel.style.display === "block") {
                panel.style.display = "none";
            } else {
                panel.style.display = "block";
            }
        });
    }
</script>
</body>
</html>
