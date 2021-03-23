<?php

    function openCsv($filename) {
           
    }

    function help() {
        echo "\e[34m List of commands:\n\e[37m";
        echo "  exit - exits the program\n";
        echo "  help - lists all commands \n";
        echo "\e[39m\n"; //back to default color
    }

    while (True) {
        //User input loop
        $input = rtrim(fgets(STDIN));

        if (strcasecmp($input, "exit") == 0) {
            //Command Exit: Exits the program
            exit();
        } elseif (strcasecmp($input, "help") == 0) {
            help();
        }
        else {
            echo "\e[31m Unrecognized Command: \"$input\" Type \"help\" for a list of commands\n\e[39m";
        }
    }
?>