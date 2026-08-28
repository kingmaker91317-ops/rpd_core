<?php

$privatekey = <<<EOD
-----BEGIN PRIVATE KEY-----
MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBANF5vcpEtIT2/xJI
pkLkRPAHCQWOTia3D5ZvnHw85Aa7EHn0eMiTug8AITzMk1J40bzdgjmXpKnJWNWg
Sa8YGMAvBrWXVKiV7BNqx3O/ouR/lXqZ3Tvi+eLIOnFyeGIOgZWvKj9suKy3762P
CpUzyGO/9xZD+K/lVf3kzQs4DX1pAgMBAAECgYBOmM07bZgGI98E7zli899h6GHc
Mc7k+97fZTVj8DzmwZ2nBLGlILi5FCVkeKX2VdrscHiCP4HLKv8n+KJtDi+Kjg1S
i04rrBaeuXAHx8Oh6mfOR3u9HzKPfVE1gGzvY+YUsUs0VVdMlkik2NqYEVK1JijX
tPepd163ip0xb8g3uQJBAP55rGYfsPd5K00Anng+TJDQ0Nx0cmQPPMkWW9vaOPcb
c3iiR8abCq5Pm/Uii7agKTpssax38KP67Xper7UN/sMCQQDSuwuPMAW7szHoMfe9
lOxnrnB8/Mz2lLYHy6BOB5TgH5rr7cnLWS7g3WWkUqzQOgv/WKbeWBlcIVZnV4QK
fKhjAkEAvuxm7lAEpAei9yjpvGlxZI1mxqAPWwcboftGfBKj/rH31qBanaWhQ9qy
th5vGFvd0tnODAoI397Z4Z+80GhppQJBALW25zvc/ESkPFfupqQLRGQPrx6IXDIR
gHpuY9iFfyJY/p2NmiJI0DzFjX1KcYzJUUkqaBJ5Q70HXluUwt7MKeMCQFQEXB2h
0JB/RtuZCeGGUSI8o5QihaaeOq1oeqLdkrAo8sdoDuxN0t3GBP9F8B5z7IbvPO9r
eyLs5Ncjj/kT4Wk=
-----END PRIVATE KEY-----
EOD;

$priv = openssl_pkey_get_private($privatekey);

if (!$priv) {
    die("Invalid private key");
}

$details = openssl_pkey_get_details($priv);
$publicPem = $details['key'];

/*
 Convert PEM public key -> DER -> HEX
*/

$publicPem = str_replace([
    "-----BEGIN PUBLIC KEY-----",
    "-----END PUBLIC KEY-----",
    "\n",
    "\r"
], "", $publicPem);

$der = base64_decode($publicPem);

$hex = strtolower(bin2hex($der));

echo $hex . PHP_EOL;