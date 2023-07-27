<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use OmiseCharge;
use OmiseToken;

class ChargeController extends Controller
{
    //
    public function index() : View {
        return view('formcharge');
    }

    public function cardCharge(Request $req) : View {

        $token = OmiseToken::create(array(
            'card' => array(
              'name' => $req-name,
              'number' => $req->card_number,
              'expiration_month' => $req->expired_month,
              'expiration_year' => $req->expired_year,
              'security_code' => $req->security_code,
            )
          ));


        $charge = OmiseCharge::create(array(
        'amount' => $req->money,
        'currency' => 'jpy',
        'card' => $token['id']
        ));

        print_r($charge);

        return view('formcharge');
    }
}
