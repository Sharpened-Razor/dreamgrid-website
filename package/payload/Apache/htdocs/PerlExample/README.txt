
This will run any anything that Opensim and Perl runs on.


1) Install Perl from http://strawberryperl.com/

2) Install DBIX::Class:
    From the command prompt type in 'cpan DBIx::Class<ret>'.
    Also install 'cpan DBIx::Class::Schema::Loader<ret>'.

3) Install Template::Toolkit
    From the command prompt type in 'cpan Toolkit<ret>'.

4) Setup your Database Connection:

    Edit the file lib/Opensim/Util.pm and set your Opensim database name, username, and password, if different from the stock DreamGrid.
    Edit the file lib/Robust/Util.pm and set your Robust database name, username, and password, if different from the stock DreamGrid.

    These can be found in your GridCommon.ini or StandaloneCommon.ini files in the "ConnectionString" area as shown below.

; MySql
StorageProvider = "OpenSim.Data.MySQL.dll"
ConnectionString = "Data Source=localhost;Database=opensim;User ID=opensimuser;Password=opensimpassword;"

The Util.pm file should be be edited to match:

Opensim:

Opensim::Schema->connect("dbi:mysql:dbname=opensim",  # your database name is opensim
                                    "opensimuser",    # your user ID
                                    "opensimpassword",# your password
                                    {quote_names => 1,}
                                   )

Robust::Schema->connect("dbi:mysql:dbname=robust",  # your database name is opensim
                                    "robustuser",    # your user ID
                                    "robustpassword",# your password
                                    {quote_names => 1,}
                                   )

5) Run it.

You can point a web server at the files and then run 'http://yourhost/listusers.plx", or just type in 'perl listusers.plx'.

And out comes a web page:

Content-Type: text/html; charset=utf-8

<!DOCTYPE html>
<head>
<title>User List</title>
</head>
<body>
<p>
Hello and thank you for using OpenSim. For more infomation visit http://opensimulator.org/wiki/Main_Page
</p>
<p>Total Users: 1</p>

<p>Ferd Frederix fred@outworldz.com</p>

</body>
</html>



