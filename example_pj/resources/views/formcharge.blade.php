<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<script type="text/javascript" src="https://cdn.omise.co/omise.js"></script>

<script async
src="https://pay.google.com/gp/p/js/pay.js"
onload="onGooglePayLoaded()"></script>
<script>

/**
 * An initialized google.payments.api.PaymentsClient object or null if not yet set
 *
 * @see {@link getGooglePaymentsClient}
 */
let paymentsClient = null;

/**
 * Define the version of the Google Pay API referenced when creating your
 * configuration
 *
 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#PaymentDataRequest|apiVersion in PaymentDataRequest}
 */
const baseRequest = {
  apiVersion: 2,
  apiVersionMinor: 0
};

/**
 * Card networks supported by your site and your gateway
 *
 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#CardParameters|CardParameters}
 * @todo confirm card networks supported by your site and gateway
 */
const allowedCardNetworks = ["AMEX", "DISCOVER", "INTERAC", "JCB", "MASTERCARD", "VISA"];

/**
 * Card authentication methods supported by your site and your gateway
 *
 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#CardParameters|CardParameters}
 * @todo confirm your processor supports Android device tokens for your
 * supported card networks
 */
// const allowedCardAuthMethods = ["PAN_ONLY", "CRYPTOGRAM_3DS"];
const allowedCardAuthMethods = ["PAN_ONLY"];


/**
 * Identify your gateway and your site's gateway merchant identifier
 *
 * The Google Pay API response will return an encrypted payment method capable
 * of being charged by a supported gateway after payer authorization
 *
 * @todo check with your gateway on the parameters to pass
 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#gateway|PaymentMethodTokenizationSpecification}
 */
const tokenizationSpecification = {
  type: 'PAYMENT_GATEWAY',
  parameters: {
    'gateway': 'omise',
    'gatewayMerchantId': '{{ env('OMISE_PUBLIC_KEY') }}'
  }
};

/**
 * Describe your site's support for the CARD payment method and its required
 * fields
 *
 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#CardParameters|CardParameters}
 */
const baseCardPaymentMethod = {
  type: 'CARD',
  parameters: {
    allowedAuthMethods: allowedCardAuthMethods,
    allowedCardNetworks: allowedCardNetworks
  }
};

const cardPaymentMethod = Object.assign(
  {},
  baseCardPaymentMethod,
  {
    tokenizationSpecification: tokenizationSpecification
  }
);


/**
 * Return an active PaymentsClient or initialize
 *
 * @see {@link https://developers.google.com/pay/api/web/reference/client#PaymentsClient|PaymentsClient constructor}
 * @returns {google.payments.api.PaymentsClient} Google Pay API client
 */
function getGooglePaymentsClient() {
  if ( paymentsClient === null ) {
    paymentsClient = new google.payments.api.PaymentsClient({environment: 'TEST'});
  }
  return paymentsClient;
}

/**
 * Configure your site's support for payment methods supported by the Google Pay
 * API.
 *
 * Each member of allowedPaymentMethods should contain only the required fields,
 * allowing reuse of this base request when determining a viewer's ability
 * to pay and later requesting a supported payment method
 *
 * @returns {object} Google Pay API version, payment methods supported by the site
 */
function getGoogleIsReadyToPayRequest() {
  return Object.assign(
      {},
      baseRequest,
      {
        allowedPaymentMethods: [baseCardPaymentMethod]
      }
  );
}

/**
 * Initialize Google PaymentsClient after Google-hosted JavaScript has loaded
 *
 * Display a Google Pay payment button after confirmation of the viewer's
 * ability to pay.
 */
function onGooglePayLoaded() {
  const paymentsClient = getGooglePaymentsClient();
  paymentsClient.isReadyToPay(getGoogleIsReadyToPayRequest())
      .then(function(response) {
        if (response.result) {
          addGooglePayButton();
          // @todo prefetch payment data to improve performance after confirming site functionality
          // prefetchGooglePaymentData();
        }
      })
      .catch(function(err) {
        // show error in developer console for debugging
        console.error(err);
      });
}
/**
 * Add a Google Pay purchase button alongside an existing checkout button
 *
 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#ButtonOptions|Button options}
 * @see {@link https://developers.google.com/pay/api/web/guides/brand-guidelines|Google Pay brand guidelines}
 */
function addGooglePayButton() {
  const paymentsClient = getGooglePaymentsClient();
  const button =
      paymentsClient.createButton({onClick: onGooglePaymentButtonClicked});
  document.getElementById('googlepay').appendChild(button);
}

/**
 * Show Google Pay payment sheet when Google Pay payment button is clicked
 */
function onGooglePaymentButtonClicked() {
  const paymentDataRequest = getGooglePaymentDataRequest();
  paymentDataRequest.transactionInfo = getGoogleTransactionInfo();

  const paymentsClient = getGooglePaymentsClient();
  paymentsClient.loadPaymentData(paymentDataRequest)
      .then(function(paymentData) {
        // handle the response
        console.log(paymentData);
        console.log(paymentData.paymentMethodData.tokenizationData.token);
        var token = paymentData.paymentMethodData.tokenizationData.token;
        // document.getElementById('token').value = paymentData.paymentMethodData.tokenizationData.token;
        // document.getElementById('google_pay_form').submit();

//         processPayment(paymentData);

        Omise.setPublicKey("{{ env('OMISE_PUBLIC_KEY') }}");

        tokenParameters = {
          method: 'googlepay',
          data: token,

          // Add your billing information here (optional)
          billing_name: 'John Doe',
          billing_street1: '1600 Amphitheatre Parkway',
        };

        Omise.createToken('tokenization', tokenParameters, function(statusCode, response) {
          console.log(response)
          console.log(response.id)
          document.getElementById('card_id').value = response.id;
          document.getElementById('google_pay_form').submit();
        });
      })
      .catch(function(err) {
        // show error in developer console for debugging
        console.error(err);
      });
}

/**
 * Configure support for the Google Pay API
 *
 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#PaymentDataRequest|PaymentDataRequest}
 * @returns {object} PaymentDataRequest fields
 */
function getGooglePaymentDataRequest() {
  const paymentDataRequest = Object.assign({}, baseRequest);
  paymentDataRequest.allowedPaymentMethods = [cardPaymentMethod];
  paymentDataRequest.transactionInfo = getGoogleTransactionInfo();
  paymentDataRequest.merchantInfo = {
    // @todo a merchant ID is available for a production environment after approval by Google
    // See {@link https://developers.google.com/pay/api/web/guides/test-and-deploy/integration-checklist|Integration checklist}
    // merchantId: '01234567890123456789',
    merchantName: 'PRONTO Test Merchant'
  };
  return paymentDataRequest;
}


/**
 * Provide Google Pay API with a payment amount, currency, and amount status
 *
 * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#TransactionInfo|TransactionInfo}
 * @returns {object} transaction info, suitable for use as transactionInfo property of PaymentDataRequest
 */
function getGoogleTransactionInfo() {
  return {
    countryCode: 'JP',
    currencyCode: 'JPY',
    totalPriceStatus: 'FINAL',
    // set to cart total
    totalPrice: '100'
  };
}


</script>  
</head>

<body>
<div id="credit-card-manual">
カード決済(Manual):
<form method=POST action="/create_charge">
@csrf <!-- {{ csrf_field() }} -->
金額:<input type="text" name="money"><br />
名前:<input type="text" name="name"><br />
カード番号:<input type="text" name="card_number"><br />
セキュリティコード:<input type="text" name="security_code"><br />
カードの有効期限(月):<input type="text" name="expired_month"><br />
カードの有効期限(年):<input type="text" name="expired_year"><br />
<input type="submit" value="支払う">
</form>
</div>
<hr />
<div id="credit-card-opnpayment">
カード決済(Omise PreBuild Form):
<form method=POST action="/create_charge_omise">
@csrf <!-- {{ csrf_field() }} -->

<script type="text/javascript" src="https://cdn.omise.co/omise.js"
          data-key="{{ env('OMISE_PUBLIC_KEY') }}"
          data-amount="100"
          data-currency="JPY"
          data-default-payment-method="credit_card">
  </script>
</form>
</div>
<hr />

<div id="credit-card-opnpayment">
カード決済(Omise PreBuild Form another):

<form id="checkoutForm" method="POST" action="/create_charge_omise_another">
@csrf <!-- {{ csrf_field() }} -->
金額:<input type="text" name="money" id="payment_amount"> <br />

  <input type="hidden" name="omiseToken">
  <input type="hidden" name="omiseSource">
  <button type="submit" id="checkoutButton">Checkout</button>
</form>
</div>
<hr />

<div id="paypay">
PayPay決済(ソース課金同時):
<form method=POST action="/charge_paypay">
@csrf <!-- {{ csrf_field() }} -->
金額:<input type="text" name="money"><br />
<input type="submit" value="支払う">
</form>
</div>
<hr />
<div id="paypay-another">
PayPay決済(ソース課金を別):
<form method=POST action="/charge_paypay_another">
@csrf <!-- {{ csrf_field() }} -->
金額:<input type="text" name="money"><br />
<input type="submit" value="支払う">
</form>
</div>
<hr />
<div id="googlepay">
Google Pay:
<form method=POST action="/charge_google" id="google_pay_form">
@csrf <!-- {{ csrf_field() }} -->
<input type="hidden" name="card_id" id="card_id" value="" />
</form>
</div> 
</body>
</html>

<script>
  OmiseCard.configure({
    publicKey: "{{ env('OMISE_PUBLIC_KEY') }}"
  });

  var button = document.querySelector("#checkoutButton");
  var form = document.querySelector("#checkoutForm");
  var money = document.querySelector("#payment_amount").value;

  button.addEventListener("click", (event) => {
    event.preventDefault();
    OmiseCard.open({
      amount: money,
      currency: "JPY",
      defaultPaymentMethod: "credit_card",
      onCreateTokenSuccess: (nonce) => {
          if (nonce.startsWith("tokn_")) {
              form.omiseToken.value = nonce;
          } else {
              form.omiseSource.value = nonce;
          };
        form.submit();
      }
    });
  });
</script>