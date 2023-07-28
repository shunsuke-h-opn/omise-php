<!DOCTYPE html>
<html>
<head>
        <meta charset="utf-8">
</head>

<body>
  chargeのID:{{$charge['id']}}
  <br />
  chargeのStatus:{{$charge['status']}}
  <br />

  <a href="/check_charge_status?chargeid={{$charge['id']}}">check_charge_status</a>
</body>
</html>  