<!DOCTYPE html>
<html>
<head>
	<title></title>
	<meta charset="utf-8">
</head>
<body>


    <h3>{{ $mailData['title'] }}</h3>
  
    <p>Salut,vous avez livré la commande dont reference : {{ $mailData['commande_no'] }},le numero de facture est {{ $mailData['invoice_no'] }} chez Musumba Steel.</p>

     <p>
         DESIGNED BY ICT MUSUMBA STEEL 
     </p>
    <p>@lang('messages.Merci !')</p>
</body>
</html>