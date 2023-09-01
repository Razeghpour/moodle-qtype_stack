<?php
// THIS FILE HAS BEEN GENERATED, DO NOT EDIT, EDIT THE GENERATOR.
/*
 @copyright  2023 Matti Harjula, Aalto University.
 @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
*/

require_once(__DIR__ . '/../MP_classes.php');
require_once(__DIR__ . '/parser-root.php');

class stack_maxima_parser2_equivline {

    private static $table = null;
    private static $goto = null;
    private static $dict = null;

    // Some debug features for development.
    // TODO: remove to save on checks.
    public static $debug = false;

    /**
     * The parse function takes a Lexer that produces the tokens.
     * 
     *
     * It can be told if it should insert stars or semi-colons
     * it cannot insert both. Note that the lexer also inserts stars 
     * especially in cases like "2x => 2*x".
     *
     * Finally it can be told to collect comments from the input stream or
     * just throw them away.
     * 
     * Returns an MP_Node or a parse error, wrap in something that 
     * catches those
     */
    public static function parse($lexer, $insert = false, $collectcomments = true, array &$notes = []) {
        // First check if we have the table loaded.
        if (self::$table === null) {
            $raw = file_get_contents(__DIR__ . '/lalr-Equivline.json');
            $raw = json_decode($raw, true);
            self::$table = $raw['table'];
            self::$goto = $raw['goto'];
            self::$dict = array_flip($raw['dict']);
        }

        // Shorter.
        $goto = self::$goto;
        $table = self::$table;

        // Collect comments here, for injection to statement-lists.
        $commentdump = [];

        // Insertion of extra tokens might care if we have seen whitespace.
        $whitespaceseen = false;

        // Track previous token.
        $previous = null;

        // Start with the parser stack at state 0.
        $stack = [0];
        $shifted = true;
        $t = null; // The raw token.
        $T = null; // The symbolic token. e.g. NUM.
        while (true) {
            if ($shifted) {
                $previous = $t;
                $t = $lexer->get_next_token();
                if (self::$debug) {
                    echo(json_encode($t) . "
");
                }
                while ($t !== null && ($t->type == 'WS' || $t->type == 'COMMENT')) {
                    if ($t->type === 'WS') {
                        $whitespaceseen = true;
                    }
                    if ($collectcomments === true && $t->type == 'COMMENT') {
                        $c = new MP_Comment($t->value, []);
                        $c->position['start'] = $t->position;
                        $c->position['start-row'] = $t->line;
                        $c->position['start-col'] = $t->column;
                        $c->position['end'] = 4 + mb_strlen($t->value) + $t->position;
                        $commentdump[] = $c;
                    }
                    $t = $lexer->get_next_token();
                }
                if ($t === null) {
                    // This is a magic char signaling the end of stream.
                    $T = " ";
                } else if ($t->type == 'KEYWORD' || $t->type == 'SYMBOL') {
                    $T = $t->value;
                } else {
                    $T = $t->type;
                }
                $shifted = false;
            }

            // If insertion required try it.
            if (!isset($table[$stack[count($stack)-1]][$T])) {
                // TODO: maybe we should forbid keywords as identifiers and force string wrapping opnames?
                if ($t !== null && ($t->type === 'SYMBOL' || $t->type === 'KEYWORD') && isset($table[$stack[count($stack)-1]]['ID'])) {
                    // Sometimes it is possible to interpret symbols as identifiers.
                    $c = substr($t->value, 0, 1);
                    if ($c === '%' || $c === '_' || preg_match('/\pL/iu', $c) === 1) {
                        $t->type = 'ID';
                        $T = 'ID';
                    }
                } 
                if (!isset($table[$stack[count($stack)-1]][$T]) && (($insert === '*' && isset($table[$stack[count($stack)-1]]['*'])) || ($insert === ';' && isset($table[$stack[count($stack)-1]]['END_TOKEN'])))) {
                    $lexer->return_token($t);
                    $T = $insert;
                    $t = new stack_maxima_token('SYMBOL', $insert, -1, -1, -1, mb_strlen($insert));
                    if ($whitespaceseen) {
                        $t->note = 'inserted with whitespace';
                        if (array_search('spaces', $notes) === false) {
                            $notes[] = 'spaces';
                        }
                    } else {
                        $t->note = 'inserted without whitespace';
                        if (array_search('missing_stars', $notes) === false) {
                            $notes[] = 'missing_stars';
                        }
                    }
                    if (array_search($insert, $lexer->options->statementendtokens) !== false) {
                        $t->type = 'END_TOKEN';
                        $T = 'END_TOKEN';
                    }
                    $whitespaceseen = false;
                } 
                if (!isset($table[$stack[count($stack)-1]][$T])) {
                    // Error got $t, was expecting these...
                    throw new stack_parser_exception('Unexpected token.', array_keys($table[$stack[count($stack)-1]]), $t, $t !== null ? ['row' => $t->line, 'char' => $t->column, 'position' => $t->position] : null, $lexer->original, $previous, array_filter($stack, 'is_mp_object'));
                }
            }

            $action = $table[$stack[count($stack)-1]][$T];

            if ($action[0] === 0) {
                // Do a shift.
                $stack[] = $t;
                $stack[] = $action[1];
                $shifted = true;
            } else {
                // Time for reduce.
                $rule = $action[1];
                $tokens = [];

                if ($action[2] > 0) {
                    // This may confuse you, read into the handling of the stack in LALR parsing.
                    $tmp = array_slice($stack, -$action[2]*2);
                    array_walk($tmp, function($value, $key) use (&$tokens) {
                        if ($key % 2 === 0) {
                            $tokens[] = $value;
                        }
                    });                    
                    $stack = array_slice($stack, 0, -$action[2]*2);
                }

                // Reduce to this var.
                $term = null;

                // Turn the tokens array into shorter variables.
                $term0 = array_shift($tokens);
                $term1 = array_shift($tokens);
                $term2 = array_shift($tokens);
                $term3 = array_shift($tokens);
                $term4 = array_shift($tokens); // We don't currently have a grammar of longer definition.

                switch ($rule) {
                    case 0: // Start.
                    case 11: // Statement.
                    case 15: // Term.
                    case 18: // IndexableOrCallable.
                    case 19: // IndexableOrCallable.
                    case 20: // IndexableOrCallable.
                    case 25: // TopOp.
                    case 26: // TopOp.
                    case 27: // TopOp.
                    case 28: // TopOp.
                    case 29: // TopOp.
                        $term = $term0;
                        break;
                    case 1: // Equivline.
                        $term = new MP_Prefixeq($term1);
                        $term->position = ['start' => $term0->position, 'start-row' => $term0->line, 'start-col' => $term0->column, 'end' => $term1->position['end']];
                        break;
                    case 2: // Equivline.
                        $term = new MP_Let($term1);
                        $term->position = ['start' => $term0->position, 'start-row' => $term0->line, 'start-col' => $term0->column, 'end' => $term1->position['end']];
                        break;
                    case 3: // Equivline.
                        $term = new MP_Statement($term0, []);
                        $term->position = array_merge($term0->position, []);
                        break;
                    case 4: // List.
                        $term = new MP_List($term1);
                        $term->position = ['start' => $term0->position, 'start-col' => $term0->column, 'start-row' => $term0->line, 'end' => $term2->position + 1];
                        break;
                    case 5: // Set.
                        $term = new MP_Set($term1);
                        $term->position = ['start' => $term0->position, 'start-col' => $term0->column, 'start-row' => $term0->line, 'end' => $term2->position + 1];
                        break;
                    case 6: // Group.
                        $term = new MP_Group($term1);
                        $term->position = ['start' => $term0->position, 'start-col' => $term0->column, 'start-row' => $term0->line, 'end' => $term2->position + 1];
                        break;
                    case 7: // StatementNullList.
                    case 22: // ListsOrGroups.
                    case 24: // ListsOrGroups.
                        $term = array_merge([$term0], $term1);
                        break;
                    case 8: // StatementNullList.
                    case 10: // TermList.
                    case 23: // ListsOrGroups.
                        $term = [];
                        break;
                    case 9: // TermList.
                        $term = array_merge([$term1], $term2);
                        break;
                    case 12: // Term.
                        $term = new MP_Boolean($term0->value);
                        $term->position = ['start' => $term0->position, 'start-col' => $term0->column, 'start-row' => $term0->line, 'end' => $term0->position + $term0->length];
                        break;
                    case 13: // Term.
                        $term = new MP_Integer($term0->value, $term0->value);
                        $term->position = ['start' => $term0->position, 'start-col' => $term0->column, 'start-row' => $term0->line, 'end' => $term0->position + $term0->length];
                        break;
                    case 14: // Term.
                        $term = new MP_Float($term0->value, $term0->value);
                        $term->position = ['start' => $term0->position, 'start-col' => $term0->column, 'start-row' => $term0->line, 'end' => $term0->position + $term0->length];
                        break;
                    case 16: // IndexableOrCallable.
                        $term = new MP_String($term0->value);
                        $term->position = ['start' => $term0->position, 'start-col' => $term0->column, 'start-row' => $term0->line, 'end' => $term0->position + $term0->length];
                        break;
                    case 17: // IndexableOrCallable.
                        $term = new MP_Identifier($term0->value);
                        $term->position = ['start' => $term0->position, 'start-col' => $term0->column, 'start-row' => $term0->line, 'end' => $term0->position + $term0->length];
                        break;
                    case 21: // CallOrIndex?.
                        $term = $term0;
                        while (count($term1) > 0) {
                            $item = array_shift($term1);
                            if ($item instanceof MP_List) {
                                $term = new MP_Indexing($term, [$item]);
                            } else if ($item instanceof MP_Group) {
                                $term = new MP_FunctionCall($term, $item->items);
                            }
                            $term->position['start'] = $term0->position['start'];
                            $term->position['start-col'] = $term0->position['start-col'];
                            $term->position['start-row'] = $term0->position['start-row'];
                            $term->position['end'] = $item->position['end'];
                        }
                        break;
                    case 30: // OpPrefix.
                    case 31: // OpPrefix.
                    case 32: // OpPrefix.
                    case 33: // OpPrefix.
                    case 34: // OpPrefix.
                    case 35: // OpPrefix.
                    case 36: // OpPrefix.
                    case 37: // OpPrefix.
                    case 38: // OpPrefix.
                    case 39: // OpPrefix.
                    case 40: // OpPrefix.
                    case 41: // OpPrefix.
                        $term = new MP_PrefixOp($term0->value, $term1);
                        $term->position = ['start' => $term0->position, 'start-col' => $term0->column, 'start-row' => $term0->line, 'end' => $term1->position['end']];
                        break;
                    case 42: // OpSuffix.
                    case 43: // OpSuffix.
                        $term = new MP_PostfixOp($term1->value, $term0);
                        $term->position = ['start' => $term0->position['start'], 'start-col' => $term0->position['start-col'], 'start-row' => $term0->position['start-row'], 'end' => $term1->position + $term1->length];
                        break;
                    case 44: // OpInfix.
                    case 45: // OpInfix.
                    case 46: // OpInfix.
                    case 47: // OpInfix.
                    case 48: // OpInfix.
                    case 49: // OpInfix.
                    case 51: // OpInfix.
                    case 52: // OpInfix.
                    case 53: // OpInfix.
                    case 54: // OpInfix.
                    case 55: // OpInfix.
                    case 56: // OpInfix.
                    case 57: // OpInfix.
                    case 58: // OpInfix.
                    case 59: // OpInfix.
                    case 60: // OpInfix.
                    case 61: // OpInfix.
                    case 62: // OpInfix.
                    case 63: // OpInfix.
                    case 64: // OpInfix.
                    case 65: // OpInfix.
                    case 66: // OpInfix.
                    case 67: // OpInfix.
                    case 68: // OpInfix.
                    case 69: // OpInfix.
                    case 70: // OpInfix.
                    case 71: // OpInfix.
                    case 72: // OpInfix.
                    case 73: // OpInfix.
                    case 74: // OpInfix.
                    case 75: // OpInfix.
                        $term = new MP_Operation($term1->value, $term0, $term2);
                        $term->position = ['start' => $term0->position['start'], 'start-col' => $term0->position['start-col'], 'start-row' => $term0->position['start-row'], 'end' => $term2->position['end']];
                        break;
                    case 50: // OpInfix.
                        $term = new MP_Operation($term1->value, $term0, $term2);
                        $term->position = ['start' => $term0->position['start'], 'start-col' => $term0->position['start-col'], 'start-row' => $term0->position['start-row'], 'end' => $term2->position['end']];
                        if ($term1->note !== null) {
                            $term->position[$term1->note === 'inserted with whitespace' ? 'fixspaces' : 'insertstars'] = true;
                        }
                        break;
                    case 76: // Abs.
                        $term = new MP_FunctionCall(new MP_Identifier('abs'), [$term1]);
                        $term->position = ['start' => $term0->position, 'start-col' => $term0->column, 'start-row' => $term0->line, 'end' => $term2->position + 1];
                        break;

                    

                    default:
                        return ['error', 'unknown rule in reduce'];
                }


                // Push the reduced on back into stack.
                $stack[] = $term;

                // If we reached the start rule end here.
                if ($action[3] === 'Start') {
                    // The result should be on the top of the stack.
                    return $stack[1];
                }
                
                // Where to next?
                $stack[] = $goto[$stack[count($stack)-2]][$action[3]];

                // After reduce we need to track whitespace again.
                $whitespaceseen = false;
            }
        }

        // The result should be on the top of the stack.
        return end($stack);
    }

}
