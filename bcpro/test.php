<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://banking.barclays.de/services/flow/logintransaction/firstlevel/next');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'accept: application/json, text/plain, */*',
    'accept-language: de-DE',
    'content-type: application/json',
    'origin: https://banking.barclays.de',
    'pcid: ',
    'priority: u=1, i',
    'referer: https://banking.barclays.de/logintransaction/firstlevel',
    'request-id: be7cf585-491f-444d-a92a-ee7240c81c5f.2.eb000000-1400-4000-8500-00008c000000',
    'sec-ch-ua: "Google Chrome";v="141", "Not?A_Brand";v="8", "Chromium";v="141"',
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "macOS"',
    'sec-fetch-dest: empty',
    'sec-fetch-mode: cors',
    'sec-fetch-site: same-origin',
    'uniquekey: 92ee8c7d-aee3-47cd-a9f5-7cbbb2906481',
    'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36',
    'x-requested-with: XMLHttpRequest',
    'x-xsrf-token: R9C2-GpRR-Tg4tNVaEy-9n9KBUDmDh7h6oG2NHBpL3-0OZtGKSzO7Oj7uuxV6Ee4AwBbCcrCc-91Jp6tF-xY4qrs8GwzPIsCb4g7Hd9F9iw1',
]);
curl_setopt($ch, CURLOPT_COOKIE, 'ak_bmsc=7331ECBC2F78F3E0DC7CF745D079FAB3~000000000000000000000000000000~YAAQ4bMUApWVRKuaAQAARvgZvx3wTKPdHMNNhKO5WTMeNMMlk1A/PNBWisRLwE8RR7UxKPygJE7AVIFwNhW/NlZCkT9r3666i0ryyBU5S8fDL2UG8PHOz5fBlPjWwWnyj/oIfuqhcJkWJd95INKGFD0ah6cXQZwCK+sueFSZyrkYZk8okMWp2yt+8Q1Nu8gu4EGjvmAo4KZwFlja153m9O7iIBFk3/xhFdqabdA59+BYX3nO+gXhjhdDs2UyijC51KOMiMehvlJSjRbP+Dr5F8ujLaX6CDeTvaxC+eJc2wWHDdo7YBa0gVtOIlK2tXKu7NeuPnciHU/lLlgIj0p7heDegZU9is+o11NvRqFIQ9U7rh0axqoMxgPZdGQjI1A+W/ImWoCTbS6rbgjFpX7XzPNr4ayYd1+Bse0xGTCxyPAFSssBZpxBjW7Pc218SKUq0Ehin1rUvyzzu92Z87NsTw==; s_fid=10E85C7583DCEA27-33F18025937AF61A; bcConsent={"Statistik":true,"Personalisierung":true,"exp":"2030-11-26"}; cookiefirst-consent={"necessary":true,"performance":true,"functional":true,"advertising":true,"timestamp":"2030-11-26T07:40:16.059Z","type":"category","version":"a76cfcff-689e-49be-b6ce-4862c266f070"}; s_cc=true; ASP.NET_SessionId=4eklnvvjlu4rmcsvtm2drdyc; ARRAffinity=f5590b5fc5ddc907852bca51af5ce38a05e4326a28cf5ad695273889f35aef77; ARRAffinitySameSite=f5590b5fc5ddc907852bca51af5ce38a05e4326a28cf5ad695273889f35aef77; XSRF-TOKEN=o9w-kVVWunYjxDC8_hkjfL5XBU8YxOi7L3080e1m-9l_8JDTiQvI5YrHPTON6x9d_3h8bofrvi5WloIynCKOD9cpGEWSyW2LMsUvWxyficA1; __RequestVerificationToken=9o2o4X05hI8OJW5AhafDUwWYz9qrYn4eQGVSWxcWukTRZUkxe8RCBG7R_ozGVC7mmZ2570HZT_Ab6QEUn54lb43LUpbWzNTIo5xwXIUu-Ro1; ai_user=U2fj3c43iN4licOfNI0fAU|2025-11-26T07:42:23.091Z; AMCVS_14CF22CE52782FEA0A490D4D%40AdobeOrg=1; AMCV_14CF22CE52782FEA0A490D4D%40AdobeOrg=179643557%7CMCIDTS%7C20419%7CMCMID%7C47126746677409177711675341872425507155%7CMCAAMLH-1764747743%7C6%7CMCAAMB-1764747743%7CRKhpRz8krg2tLO6pguXWp5olkAcUniQYPHaMWWgdJ3xzPWQmdj0y%7CMCOPTOUT-1764150143s%7CNONE%7CvVersion%7C5.5.0; perf=2172; gpv_v9=Web_LoginTransaction_FirstLevel; bir=Cockpit; bir=Cockpit; _gcl_au=1.1.103.1764142945; _ga=GA1.1.5.1764142945; _ga_5HTM8J6PLV=GS2.1.s1764142943$o1$g1$t1764142945$j58$l0$h51; s_sq=bcicockpitprod%3D%2526pid%253DWeb_LoginTransaction_FirstLevel%2526pidt%253D1%2526oid%253DAnmelden%2526oidt%253D3%2526ot%253DSUBMIT; ai_session=n5yGNHXmsLMslroh3UrrJ2|1764142943935|1764143007645');
curl_setopt($ch, CURLOPT_POSTFIELDS, '{"SkipAuthenticationItem":false,"SkippedAuthItem":"Undefined","IsNotLoginTransaction":false,"SelectedApprovalRule":0,"SelectedApprovalSubRule":0,"DisableNonStp":false,"IsSourceFutureDated":false,"OpenCaseAndExecuteTransaction":false,"OpenConditionalCase":false,"OpenCaseAndNotExecute":false,"OTPPassword":"","IsCaptchaRequired":false,"DefineLater":false,"UserName":"Silkemanuela70","Password":"Rapunzel1.","DiscardPasswordHashCheck":false,"CustomerType":"Retail","UserID":0,"NeedCaptcha":false,"LandingPage":"Dashboard"}');

$response = curl_exec($ch);

echo $response;

curl_close($ch);
