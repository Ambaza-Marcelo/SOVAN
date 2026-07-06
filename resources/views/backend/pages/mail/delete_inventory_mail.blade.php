<!DOCTYPE html>
<html>
<head>
	<title></title>
	<meta charset="utf-8">
</head>
<body>


    <h3>{{ $mailData['title'] }}</h3>
  
    <p>Salut,{{ $mailData['auteur']}} vient de supprimer l'inventaire dont reference {{ $mailData['inventory_no']}},Le Systeme EDENSOFT ne cesse jamais de vous envoyer la notification en cas des operations douteuses.</p>

     <p>
         DESIGNED BY <a href="https://www.ambazamarcellin.netlify.app">@lang('messages.AMBAZA Marcellin')</a> et Steven Habyarimana 
     </p>
    <p>@lang('messages.Merci !')</p>
</body>
</html>