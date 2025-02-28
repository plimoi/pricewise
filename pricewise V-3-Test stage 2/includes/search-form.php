<?php
// includes/search-form.php - Search form for PriceWise
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<form id="pricewise-search-form" method="post" action="">
    <div>
        <label for="destination">Destination:</label>
        <input type="text" id="destination" name="destination" autocomplete="off" required>
        <!-- Suggestions will appear dynamically below via JS -->
    </div>
    <div>
        <label for="checkin_date">Check-in Date:</label>
        <input type="date" id="checkin_date" name="checkin_date" required>
    </div>
    <div>
        <label for="checkout_date">Check-out Date:</label>
        <input type="date" id="checkout_date" name="checkout_date" required>
    </div>
    <div>
        <label for="adults">Number of Adults:</label>
        <input type="number" id="adults" name="adults" min="1" value="1" required>
    </div>
    <div>
        <label for="children">Number of Children:</label>
        <input type="number" id="children" name="children" min="0" value="0">
    </div>
    <div>
        <label for="rooms">Number of Rooms:</label>
        <input type="number" id="rooms" name="rooms" min="1" value="1" required>
    </div>
    <div>
        <label for="pets">Pets Allowed:</label>
        <input type="checkbox" id="pets" name="pets" value="1">
    </div>
    <div>
        <button type="submit">Search</button>
    </div>
</form>
