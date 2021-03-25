<?php

    error_reporting(E_ERROR | E_PARSE);    //Suppresses php warnings (comment out line for development)

    $fileHeaders;
    $fileData;

    interface Command {
        public function execute($args);
        public function argsAreValid($args);
        public function help();
    }

    $exit = new class implements Command {

        public function help() {
            echo "\e[32m exit - exits the program. Takes no arguments. \n\e[39m";
        }

        public function execute($args) {
            exit();
        }

        public function argsAreValid($args) {
            return count($args) == 0;
        }
        
    };

    $help = new class implements Command {
        public function execute($args) {
            global $commands;
            if (count ($args) == 0) {
                echo "\e[34m List of commands:\n\e[37m";
                echo "  display - displays the currently loaded file (see \"help display\" for details)\n";
                echo "  exit - exits the program\n";
                echo "  help - lists all commands \n";
                echo "  list - lists all entries under a given header \n";
                echo "  open - loads a csv file\n";
                echo "Type \"help [command]\" for more detailed information on a specific command\n";
                echo "\e[39m\n"; //back to default color
            } else {
                $commands[$args[0]]->help();
            }
        }

        public function help() {
            echo "\e[32m help - lists all commands  \n help [command] - displays information about the given command\e[39m\n";
        }

        public function argsAreValid($args) {
            global $commands;
            return count($args) == 0 || (count($args) == 1 && in_array($args[0], array_keys($commands)));
        }
    };

    $open = new class implements Command {
        public function execute($args) {
            global $fileHeaders;
            global $fileData;
            if (($handle = fopen($args[0], "r")) !== FALSE) {
                if (($fileHeaders = fgetcsv($handle, 1000, ";")) !== FALSE) {
                    $i = 0;
                    while (($fileData[$i++] = fgetcsv($handle, 1000, ";")) !== FALSE) {}
                    echo "File loaded successfully\n";
                } else {
                    echo "\e[31m error reading file\n\e[39m";
                }
            } else {
                echo "\e[31m error opening file\n\e[39m";
            }
        }

        public function help() {
            echo "\e[32m open [file] - loads a csv file into the program. \nThe first line of the file should be the headers with the remaining lines containing the data \n\e[39m";
        } 

        public function argsAreValid($args) {
            return count($args) == 1;
        }
    };

    $display = new class implements Command {
        public function execute($args) {
            global $fileData, $fileHeaders;
            if (is_null($fileHeaders) || is_null($fileData)) {
                echo "\e[31m error: no file loaded into program. Use \"open\" to load a file.\n\e[39m";
            } elseif (count($args) == 0) {
                //Display the currently loaded csv file
                $this->standardDisplay();
            } elseif (count($args) == 1) {
                if (substr_count($args[0], "=") == 1) {
                    $this->displayWithEq($args[0]);
                } elseif (substr_count($args[0], "<") == 1) {
                    $this->displayWithComparison($args[0]);
                }
            }
        }

        public function help() {
            echo "\e[32m display - displays all information in the currently loaded file\n";
            echo " display [header]=[value] - displays all rows in the loaded file that have the given header value. Note that for values or headers" . 
                    " with spaces you will need to wrap the entire argument in quotes. (i.e \e[39mdisplay \"a=Hello World\"\e[32m)\n";
            echo "See also the \"list\" command for alternative ways to display information\n";
            echo"\e[39m";
        } 

        public function argsAreValid($args) {
            if (count($args) == 1) {
                return substr_count($args[0], "=") == 1 || substr_count($args[0], "<") == 1;
            }
            return count($args) == 0;
        }

        function displayWithEq($modifier) {
            global $fileData, $fileHeaders;
            $header = explode('=', $modifier)[0];
            $value = explode('=', $modifier)[1];
            $index = array_search($header, $fileHeaders);

            if ($index === false)
                echo "\e[31m error: Header $header not found\n\e[39m";
            else {
                foreach ($fileData as &$dataline) {
                    if (strcmp($dataline[$index], $value) == 0)
                        $this->displayDataLine($dataline);
                }
                echo "\e[39m";
            }
        }

        function displayWithComparison($modifier) {
            global $fileData, $fileHeaders;
            $header = explode('<', $modifier)[0];
            $value = explode('<', $modifier)[1];
            $index = array_search($header, $fileHeaders);

            if ($index === false)
                echo "\e[31m error: Header $header not found\n\e[39m";
            else {
                foreach ($fileData as &$dataline) {
                    if (is_numeric($dataline[$index])) {
                        if (intval($dataline[$index]) < $value) {
                            $this->displayDataLine($dataline);
                        }
                    } elseif (is_array($dataline)) {
                        echo "\e[31m error: Non numeric value\n\e[39m";
                        echo intval($dataline[$index]) . '  ';
                        echo $dataline[$index] . "\n";
                    }
                }
            }
        }

        function standardDisplay() {
            global $fileData, $fileHeaders;
            foreach ($fileData as &$dataline) {
                $this->displayDataLine($dataline);
            }
            echo "\e[39m";
        }

        function displayDataLine($dataline) {
            global $fileHeaders;
            if (is_array($dataline)) {
                $i = 0;
                foreach ($fileHeaders as &$header) {
                    echo "\e[96m"; //Change color to light cyan
                    printf("%s: \e[37m%s", $header, $dataline[$i++]);
                    echo "\n";
                }
                echo "\n";
            }
        }
    };

    $list = new class implements Command {
        public function execute($args) {
            global $fileHeaders, $fileData;
            if (is_null($fileHeaders) || is_null($fileData)) {
                echo "\e[31m error: no file loaded into program. Use \"open\" to load a file.\n\e[39m";
            } elseif(strcmp($args[0],"-headers") == 0) {
                echo "Headers: \e[96m" . implode(", ", $fileHeaders) . "\e[39m\n";
            } elseif (in_array($args[0], $fileHeaders)) {
                $index = array_search($args[0], $fileHeaders);
                printf("\e[90mAll data under header \e[96m%s\e[39m:\n", $args[0]);
                foreach ($fileData as &$dataline) {
                    if (is_array($dataline)) {
                        print $dataline[$index] . "\n";
                    }
                }
            } else {
                printf("\e[31m error: %s not found in headers. Use \"list -headers\" to show all available headers\n\e[39m", $args[0]);
            }
        }

        public function help() {
            echo "\e[32m\nlist [header] - displays all information under a given header in the loaded file\nlist -headers - displays all headers in the loaded file\nSee also \"display\" for displaying functionality \e[39m\n";
        } 

        public function argsAreValid($args) {
            return count($args) == 1;
        }
    };

    $commands = array("exit" => $exit, "help" => $help, "open" => $open, "display" => $display, "list" => $list);

    while (True) {
        global $commands;
        //User input loop
        echo ">";
        $input = rtrim(fgets(STDIN));
        $command = explode(' ', $input)[0];
        if (in_array($command, array_keys($commands))) {
            //Build argument list with quotes allowed (ignore spaces when under quotes)
            $args = array();
            $insidequotes = false;
            $init_args = array_slice(explode(' ', $input), 1);
            foreach ($init_args as &$word) {
                if (strpos($word, '"') === 0) {
                    $insidequotes = true;
                    array_push($args, substr($word, 1) . ' ');
                } elseif (strpos($word, '"') === strlen($word) - 1) {
                    $insidequotes = false;
                    $args[count($args) - 1] .= substr($word, 0, -1);
                } elseif ($insidequotes)
                    $args[count($args) - 1] .= $word . ' ';
                else
                    array_push($args, $word);
            }

            if ($commands[$command]->argsAreValid($args))
                $commands[$command]->execute($args);
            else {
                echo "\e[31m Invalid arguments\n";
                $commands[$command]->help(); 
            }
        } else {
            echo "\e[31m Unrecognized Command: \"$input\" Type \"help\" for a list of commands\n\e[39m";
        }
    }
?>