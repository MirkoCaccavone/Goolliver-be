<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/payment_intents");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$output = curl_exec($ch);
if ($output === false) {
    echo "CURL error: " . curl_error($ch);
} else {
    echo "CURL OK";
}
curl_close($ch);
