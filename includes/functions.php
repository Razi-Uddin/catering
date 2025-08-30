<?php
function isLoggedInAdmin() {
    return isset($_SESSION['admin']);
}
function isLoggedInCustomer() {
    return isset($_SESSION['customer_id']);
}
?>