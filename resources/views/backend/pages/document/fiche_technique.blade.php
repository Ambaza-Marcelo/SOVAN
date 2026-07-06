<!doctype html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="utf-8">
    <style type="text/css">
        tr,th,td{
             border: 1px solid black;
             text-align: center;
             width: auto;
        }
        * { font-family: "DejaVu Sans", sans-serif !important; }
    </style>
</head>
<body style="font-family: 'DejaVu Sans', sans-serif;">
<div>
    <div>
        <div>
            <div>
                <div>
                   <img src="img/logo.png" width="200" height="80">
                </div>
                <div>
                    <div>
                          <h4>
                           FICHES TECHNIQUES
                            </h4>
                    </div>
                    <div>
                        <table style="border: 1px solid black;border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th>@lang('messages.No')</th>
                                    <th>@lang('messages.Article')</th>
                                    <th>@lang('messages.Ingredients')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($datas as $data)
                               <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ $data->name }}</td>
                                    <td>
                                            @foreach($foods as $food)
                                                @if($data->code == $food->code && !empty($food->food_id))<small>{{ $food->food->name }} : {{ $food->quantity }} ({{ $food->food->foodMeasurement->production_unit }})</small>@endif
                                            @endforeach
                                        
                                    </td>          
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

