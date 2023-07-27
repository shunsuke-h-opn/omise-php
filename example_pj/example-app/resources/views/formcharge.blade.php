<!DOCTYPE html>
<html>
<head>
        <meta charset="utf-8">
</head>

<body>
  カード決済:
  <form method=POST action="/create_charge">
    金額:<input type="text" name="money"><br />
    名前:<input type="text" name="name"><br />
    カード番号:<input type="text" name="card_number"><br />
    セキュリティコード:<input type="text" name="security_code"><br />
    カードの有効期限(月):<input type="text" name="expired_month"><br />
    カードの有効期限(年):<input type="text" name="expired_year"><br />
    <input type="submit" value="支払う">
  </form>
</body>
</html>  