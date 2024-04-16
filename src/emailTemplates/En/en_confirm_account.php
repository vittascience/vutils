<!DOCTYPE html>
<html>
<head>
    <style type='text/css'>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Open+Sans:wght@400;600;700&display=swap');

        #bodyTable,
        #emailBody {
            font-family: 'Montserrat', 'Open Sans', sans-serif;
            font-size: 16px;
            line-height: 1.4;
        }


        #bodyTable {
            color: #000;
            background-color: #8DC4A780;
        }

        #emailContainer {
            margin: 10px;

            border-radius:10px;
            background-color: #fff;
            box-shadow: 0px 0px 10px #0000004D;
        }

        #emailFooter a {
            display: inline-block;
            text-align: center;
            margin: 10px 20px;
        }

        #emailFooter a img {
            object-fit: contain;
            display: block;
            width: 50px;
            height: 50px;
            margin: 0 auto;
        }
        .btn {
            display: inline-block;
            font-weight: 400;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.25rem;
            transition: color 0.15s ease-in-out,background-color 0.15s ease-in-out,border-color 0.15s ease-in-out,box-shadow 0.15s ease-in-out;
        }
        .v-btn {
            -webkit-appearance: none !important;
            border-radius: 250em;
            font-weight: 600;
            border: 2px solid transparent;
            transition: all 0.15s ease-in-out;
            background-color: #22b573;
            color: white !important;
            text-decoration: none;
        }
        .v-btn:hover {
            box-shadow: 0px 4px 5px rgba(0, 0, 0, 0.2);
            color: white;
            cursor: pointer;
        }
        .v-btn:focus {
            box-shadow: 0px 0px 10px #3fa9f5;
            border: 2px solid white;
        }
    </style>
</head>
<body>
<table border='0' cellpadding='0' cellspacing='0' height='100%' width='100%' id='bodyTable'>
    <tr>
        <td align='center' valign='top'>
            <table border='0' cellpadding='20' cellspacing='0' width='600' id='emailContainer' style='box-shadow: 0px 0px 10px #0000004D;'>
                <tr>
                    <td align='center' valign='top'>
                        <table border='0' cellpadding='5' cellspacing='0' width='100%' id='emailHeader'>
                            <tr>
                                <td align='center' valign='top'>
                                    <img src='https://vittascience.com/public/content/img/vittascience2small.png'  title='Vittascience' style='display:block; width:150px; object-fit:contain; height:auto; margin: 0px auto;'>                                    
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td align='center' valign='top'>
                        <table border='0' cellpadding='10' cellspacing='0' width='100%' id='emailBody'>
                            <tr>
                                <td align='left' valign='top'><?= $body ?></td>
                            </tr>
                            <tr>
                                <td align='center' valign='top'>
                                    <p style='color: #565656; text-align: center;'>
                                    Scientifically yours,<br>
                                    <span style='color:#24A069;'>The Vittascience team</span>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td align='center' valign='top'>
                        <table border='0' cellpadding='5' cellspacing='0' width='100%' id='emailFooter'>
                            <tr>
                                <td align='center' valign='top'>
                                    <div style='width:100%; margin: 10px 0; text-align:center;'>
                                        <a href='https://www.linkedin.com/company/vittascience'>
                                            <img src='https://vittascience.com/public/content/img/mails/linkedin%20gris.png' title='LinkedIn'>    
                                        </a>

                                        <a href='https://www.facebook.com/vittascience'>
                                            <img src='https://vittascience.com/public/content/img/mails/facebook%20gris.png' title='Facebook'>
                                        </a>
                                        <a href='https://www.twitter.com/vittascience'>
                                            <img src='https://vittascience.com/public/content/img/mails/twitter%20gris.png' title='Twitter'>
                                        </a>
                                    </div>
                                    <span>Copyright © 2024 Vittascience</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>