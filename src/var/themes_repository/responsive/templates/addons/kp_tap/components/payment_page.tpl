
<html>

<body>
    

    
    <input type="button" value="Place order by Tap" id="submit_tap_payment_form" onclick="goSell.openLightBox()" />
    <input type="button" value="Place order by Tap" id="submit_tap_payment_form" onclick="goSell.openPaymentPage()" />
<div id="root"></div>
</body>
{scripts}
     <link rel="shortcut icon" href="//goSellJSLib.b-cdn.net/v1.6.1/imgs/tap-favicon.ico" />
    <link href="//goSellJSLib.b-cdn.net/v1.6.1/css/gosell.css" rel="stylesheet" />
    <script type="text/javascript" src="//goSellJSLib.b-cdn.net/v1.6.1/js/gosell.js"></script>
    
<script>
//$return_url = "http://localhost/cs-cartnNEW4/index.php?dispatch=payment_notification.notify&payment=tap&order_id=123&secretkey=sk_test_kovrMB0mupFJXfNZWx6Etg5y";
        
    setTimeout(function(){ 
     //$('#button-confirm').on('click', function() {
        // console.log('{{$paymentmode}}');
        // console.log('{$payed_url nofilter}}');
        //alert("here");

        if('{$language}'=='english'){
            var language = 'en';
            var labels = {
                                    cardNumber:"Card Number",
                                    expirationDate:"MM/YY",
                                    cvv:"CVV",
                                    cardHolder:"Card Holder Name"
                                };
                                var direction = 'ltr';
        }
        else{
            var language ='ar';
            var labels = {
                                    cardNumber:"رقم البطاقة",
                                    expirationDate:"شهر/سنة",
                                    cvv:"التحقق من البطاقة",
                                    cardHolder:"الاسم موجود على البطاقة"
                                };
                                var direction = 'rtl';
        }
        if('{{$savecard}}' == 'no'){
            var savecard = 'false';
        }
        else{
            var savecard = 'true';
        }
        if('{{$paymentmode}}' == 'authorize'){
            var object_trans = {
                    mode :'authorize',
                    authorize:{
                    auto:{
                    type:'VOID',
                     time: 100
                      },
                      saveCard: savecard,
                      threeDSecure: threed,
                      description: "",
                      statement_descriptor:"statement_descriptor",
                      reference:{
                      transaction: '',
                      order: '{$order_id}'
                        },
                        metadata:{},
                        receipt:{
                            email: false,
                            sms: true
                        },
                         redirect: '{$payed_url nofilter}',
                         post: '{{$return_url}}'
                }
              }

        }
        if ('{{$paymentmode}}' == 'charge') {
            var object_trans = {
              mode: 'charge',
                charge:{
                  saveCard: savecard,
                  threeDSecure: true,
                  description: "",
                  statement_descriptor: "Sample",
                  reference:{
                    transaction: '',
                    order: '{$order_id}'
                  },
                  metadata:{},
                  receipt:{
                    email: false,
                    sms: true
                  },                  
                  redirect: '{$payed_url nofilter}',
                  post: '{{$return_url}}'
                }
              }
        }

    //alert(savecard);
    goSell.config({
      gateway:{
        publicKey:'{$public_key}',
        language:language,
        contactInfo:true,
        supportedCurrencies:"all",
        supportedPaymentMethods: "all",
        saveCardOption:savecard,
        customerCards: true,
        notifications:'standard',
         callback:(response) => {
            console.log('response', response);
        },
        backgroundImg: {
          url: '',
          opacity: '0.5'
        },
        labels:labels,
        style: {
            base: {
              color: '#535353',
              lineHeight: '18px',
              fontFamily: 'sans-serif',
              fontSmoothing: 'antialiased',
              fontSize: '16px',
              '::placeholder': {
                color: 'rgba(0, 0, 0, 0.26)',
                fontSize:'15px'
              }
            },
            invalid: {
              color: 'red',
              iconColor: '#fa755a '
            }
        }
      },
      customer:{
        id:'',
        first_name: '{$order_info['firstname']}',
        middle_name: "Middle Name",
        last_name: '{$order_info['lastname']}',
        email: '{$customer.email}',
        phone: {
            country_code: '',
            number:'{$customer.phone}'
        }
      },
      order:{
        amount: '{$order_info['total']}',
        currency:'{$order_info['secondary_currency']}',
        shipping:null,
        taxes: null
      },
     transaction:object_trans
    });
   if ( '{$processor_data['processor_params']['uimode']}' == 'redirect') {          
                    window.onload=function(){ 
                        goSell.openPaymentPage();
                    };

 }
 if ( '{$processor_data['processor_params']['uimode']}' == 'popup') {  
    window.onload=function(){ 
                        goSell.openLightBox();
                    };
    
 }
  
    
    }, 1000);

</script>

{/scripts}

