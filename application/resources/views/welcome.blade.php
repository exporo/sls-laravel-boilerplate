<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Laravel</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet" type="text/css">

    <!-- Styles -->
    <style>
        html, body {
            background-color: #fff;
            color: #636b6f;
            font-family: 'Nunito', sans-serif;
            font-weight: 200;
            height: 100vh;
            margin: 0;
        }

        .full-height {
            height: 100vh;
        }

        .flex-center {
            align-items: center;
            display: flex;
            justify-content: center;
        }

        .position-ref {
            position: relative;
        }

        .top-right {
            position: absolute;
            right: 10px;
            top: 18px;
        }

        .content {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .title {
            font-size: 84px;
        }

        .links > a {
            color: #636b6f;
            padding: 0 25px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: .1rem;
            text-decoration: none;
            text-transform: uppercase;
        }

        .m-b-md {
            margin-bottom: 30px;
        }

        input {
            text-align: right;
            width: 50px;
        }
    </style>
</head>
<body>
<div class="flex-center position-ref full-height">
    <div class="content">
        <img width="400px" src="{{ asset('exporo-tech.png') }}">
        <div class="title m-b-md" >
            SLS Laravel Boilerplate
        </div>
        <hr>
        <div class="text-left ">
            This demo application implements 3 different page counters, which uses a SQS Queue to store the hits asynchronously.<br>
            A cron job resets all page counters hourly.
        </div>
        <table style="width:50%">
            <tbody>
            @foreach($counter as $type => $value)
                <tr>
                    <td class="text-left"><b>{{ $type }}</b></td>
                    <td><b>{{ $value }} page hits</b></td>
                    <br>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
