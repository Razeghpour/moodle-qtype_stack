<?php
// THIS FILE HAS BEEN GENERATED, DO NOT EDIT, EDIT THE GENERATOR.
/*
 @copyright  2023 Matti Harjula, Aalto University.
 @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
*/

require_once(__DIR__ . '/../MP_classes.php');

class stack_parser_exception extends Exception {
    // What tokens were expected.
    public $expected = null;
    // What was seen, the full token.
    public $received = null;
    // Where it was seen if known.
    public $position = null;
    // The full original code.
    public $original = null;
    // The previous token.
    public $previous = null;
    // Partial results, i.e. all the MP-objects we can recover 
    // from the parsers stack. Might let one to give some context.
    public $partial = null;

    public function __construct($message, $expected, $received, $position, $original, $previous, $partial) {
        parent::__construct($message . ' Expected' . json_encode($expected) . ' received ' . json_encode($received));
        $this->expected = $expected;
        $this->received = $received;
        $this->position = $position;
        $this->original = $original;
        $this->previous = $previous;
        $this->partial = $partial;
    }

    public function __toString() {
        return 'Expected [' . implode(',', $this->expected) . '] received ' . json_encode($this->received) . ' at ' . json_encode($this->position);
    }
}

/**
 * A predicate for certain filter operations. 
 */
function is_mp_object($x): bool {
    return $x instanceof MP_Node;
}

class stack_maxima_parser2_root {

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
    public static function parse($lexer, $insert = false, $collectcomments = true, array &$notes = []) {        // First check if we have the table loaded.
        if (self::$table === null) {
            $raw = file_get_contents(__DIR__ . '/lalr-Root.json');
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
                if (self::$debug) {
                    echo(json_encode($t) . "
");
                }
            }

            // If insertion required try it.
            if (!isset($table[$stack[count($stack)-1]][$T])) {
                // TODO: maybe we should forbid keywords as identifiers and force string wrapping opnames?
                if ($t !== null && ($t->type === 'SYMBOL' || $t->type === 'KEYWORD') && isset($table[$stack[count($stack)-1]]['ID'])) {
                    // Sometimes it is possible to interpret symbols as identifiers.
                    $c = substr($t->value, 0, 1);
                    if ($c === '%' || $c === '_' || (preg_match('/\pL/iu', $c) === 1)) {
                        $t->type = 'ID';
                        $T = 'ID';
                        if (self::$debug) {
                            echo("SYMBOL/KEYWORD->ID
");
                        }
                    }
                } 
                if (!isset($table[$stack[count($stack)-1]][$T]) && (($insert === '*' && isset($table[$stack[count($stack)-1]]['*'])) || ($insert === ';' && isset($table[$stack[count($stack)-1]]['END_TOKEN'])))) {
                    // Only support these two and only insert if possible.
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
                    if (self::$debug) {
                        echo("return and insert " . json_encode($t) . "
");
                    }
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
                    case 18: // Statement.
                    case 22: // Term.
                    case 26: // IndexableOrCallable.
                    case 27: // IndexableOrCallable.
                    case 28: // IndexableOrCallable.
                    case 33: // Term.
                    case 34: // Flow.
                    case 35: // Flow.
                    case 51: // TopOp.
                    case 52: // TopOp.
                    case 53: // TopOp.
                    case 54: // TopOp.
                    case 55: // TopOp.
                        $term = $term0;
                        break;
                    case 1: // Root.
                        $term = new MP_Root([]);
                        $term->position = ['start'=>1,'end'=>1,'start-col'=>1,'start-row'=>1];
                        break;
                    case 2: // Root.
                        $term = new MP_Root($term0);
                        $term->position = ['start' => $term0[0]->position['start'], 'start-col' => $term0[0]->position['start-col'], 'start-row' => $term0[0]->position['start-row'], 'end' => $term0[0]->position['end']];
                        $term->position['end'] = $term0[count($term0)-1]->position['end'];
                        break;
                    case 3: // StatementList.
                        $term = array_merge([new MP_Statement($term0, $term1)], $term2);
                        $term[0]->position['start'] = $term0->position['start'];
                        $term[0]->position['start-col'] = $term0->position['start-col'];
                        $term[0]->position['start-row'] = $term0->position['start-row'];
                        if (count($term1) > 0) {
                            $term[0]->position['end'] = $term1[count($term1)-1]->position['end'];
                        } else {
                            $term[0]->position['end'] = $term0->position['end'];
                        }
                        break;
                    case 4: // StatementListN.
                        $term = array_merge([new MP_Statement($term1, $term2)], $term3);
                        $term[0]->position['start'] = $term1->position['start'];
                        $term[0]->position['start-col'] = $term1->position['start-col'];
                        $term[0]->position['start-row'] = $term1->position['start-row'];
                        if (count($term2) > 0) {
                            $term[0]->position['end'] = $term2[count($term2)-1]->position['end'];
                        } else {
                            $term[0]->position['end'] = $term1->position['end'];
                        }
                        break;
                    case 5: // StatementListN.
                    case 6: // StatementListN.
                    case 10: // EvalFlags.
                    case 15: // StatementNullList.
                    case 17: // TermList.
                    case 31: // ListsOrGroups.
                    case 42: // LoopBits.
                        $term = [];
                        break;
                    case 7: // EvalFlags.
                        $term = array_merge([new MP_EvaluationFlag(new MP_Identifier($term1->value), new MP_Boolean(true))], $term2);
                        $term[0]->name->position = ['start' => $term1->position, 'start-col' => $term1->column, 'start-row' => $term1->line, 'end' => $term1->position + $term1->length];
                        $term[0]->position = ['start' => $term1->position, 'start-col' => $term1->column, 'start-row' => $term1->line, 'end' => $term1->position + $term1->length];
                        break;
                    case 8: // EvalFlags.
                    case 9: // EvalFlags.
                        $term = array_merge([new MP_EvaluationFlag(new MP_Identifier($term1->value), $term3)], $term4);
                        $term[0]->name->position = ['start' => $term1->position, 'start-col' => $term1->column, 'start-row' => $term1->line, 'end' => $term1->position + $term1->length];
                        $term[0]->position = ['start' => $term1->position, 'start-col' => $term1->column, 'start-row' => $term1->line, 'end' => $term3->position['end']];
                        break;
                    case 11: // List.
                        $term = new MP_List($term1);
                        $term->position = ['start' => $term0->position, 'start-col' => $term0->column, 'start-row' => $term0->line, 'end' => $term2->position + 1];
                        break;
                    case 12: // Set.
                        $term = new MP_Set($term1);
                        $term->position = ['start' => $term0->position, 'start-col' => $term0->column, 'start-row' => $term0->line, 'end' => $term2->position + 1];
                        break;
                    case 13: // Group.
                        $term = new MP_Group($term1);
                        $term->position = ['start' => $term0->position, 'start-col' => $term0->column, 'start-row' => $term0->line, 'end' => $term2->position + 1];
                        break;
                    case 14: // StatementNullList.
                    case 30: // ListsOrGroups.
                    case 32: // ListsOrGroups.
                    case 41: // LoopBits.
                        $term = array_merge([$term0], $term1);
                        break;
                    case 16: // TermList.
                        $term = array_merge([$term1], $term2);
                        break;
                    case 19: // Term.
                        $term = new MP_Boolean($term0->value);
                        $term->position = ['start' => $term0->position, 'start-col' => $term0->column, 'start-row' => $term0->line, 'end' => $term0->position + $term0->length];
                        break;
                    case 20: // Term.
                        $term = new MP_Integer($term0->value, $term0->value);
                        $term->position = ['start' => $term0->position, 'start-col' => $term0->column, 'start-row' => $term0->line, 'end' => $term0->position + $term0->length];
                        break;
                    case 21: // Term.
                        $term = new MP_Float($term0->value, $term0->value);
                        $term->position = ['start' => $term0->position, 'start-col' => $term0->column, 'start-row' => $term0->line, 'end' => $term0->position + $term0->length];
                        break;
                    case 23: // IndexableOrCallable.
                        $term = new MP_String($term0->value);
                        $term->position = ['start' => $term0->position, 'start-col' => $term0->column, 'start-row' => $term0->line, 'end' => $term0->position + $term0->length];
                        break;
                    case 24: // IndexableOrCallable.
                        $term = new MP_Identifier($term0->value);
                        $term->position = ['start' => $term0->position, 'start-col' => $term0->column, 'start-row' => $term0->line, 'end' => $term0->position + $term0->length];
                        break;
                    case 25: // IndexableOrCallable.
                        $term = new MP_Identifier($term0->value . $term1->value);
                        
                        $term->position = ['start' => $term0->position, 'start-col' => $term0->column, 'start-row' => $term0->line, 'end' => $term1->position + $term1->length];
                        break;
                    case 29: // CallOrIndex?.
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
                    case 36: // IfBase.
                        $term = new MP_If(array_merge([$term1], $term4[0]), array_merge([$term3], $term4[1]));
                        $endposition = count($term->branches) > 0 ? $term->branches[count($term->branches)-1] : $term3;
                        $term->position = ['start' => $term0->position, 'start-col' => $term0->column, 'start-row' => $term0->line, 'end' => $endposition->position['end']];
                        break;
                    case 37: // IfTail.
                        $term = [[],[$term1]];
                        break;
                    case 38: // IfTail.
                        $term = [array_merge([$term1], $term4[0]), array_merge([$term3], $term4[1])];
                        break;
                    case 39: // IfTail.
                        $term = [[],[]];
                        break;
                    case 40: // Loop.
                        $term = new MP_Loop($term2, $term0);
                        $term->position = ['end' => $term2->position['end']];
                        $term->position['start'] = count($term->conf) ? $term->conf[0]->position['start'] : $term1->position;
                        $term->position['start-row'] = count($term->conf) ? $term->conf[0]->position['start-row'] : $term1->line;
                        $term->position['start-col'] = count($term->conf) ? $term->conf[0]->position['start-col'] : $term1->column;
                        break;
                    case 43: // LoopBit.
                    case 44: // LoopBit.
                    case 45: // LoopBit.
                    case 46: // LoopBit.
                    case 47: // LoopBit.
                    case 48: // LoopBit.
                    case 49: // LoopBit.
                    case 50: // LoopBit.
                        $term = new MP_LoopBit($term0->value, $term1);
                        $term->position = ['start' => $term0->position, 'start-col' => $term0->column, 'start-row' => $term0->line, 'end' => $term1->position['end']];
                        break;
                    case 56: // OpPrefix.
                    case 57: // OpPrefix.
                    case 58: // OpPrefix.
                    case 59: // OpPrefix.
                    case 60: // OpPrefix.
                    case 61: // OpPrefix.
                    case 62: // OpPrefix.
                    case 63: // OpPrefix.
                    case 64: // OpPrefix.
                    case 65: // OpPrefix.
                    case 66: // OpPrefix.
                    case 67: // OpPrefix.
                        $term = new MP_PrefixOp($term0->value, $term1);
                        $term->position = ['start' => $term0->position, 'start-col' => $term0->column, 'start-row' => $term0->line, 'end' => $term1->position['end']];
                        break;
                    case 68: // OpSuffix.
                    case 69: // OpSuffix.
                        $term = new MP_PostfixOp($term1->value, $term0);
                        $term->position = ['start' => $term0->position['start'], 'start-col' => $term0->position['start-col'], 'start-row' => $term0->position['start-row'], 'end' => $term1->position + $term1->length];
                        break;
                    case 70: // OpInfix.
                    case 71: // OpInfix.
                    case 72: // OpInfix.
                    case 73: // OpInfix.
                    case 74: // OpInfix.
                    case 75: // OpInfix.
                    case 77: // OpInfix.
                    case 78: // OpInfix.
                    case 79: // OpInfix.
                    case 80: // OpInfix.
                    case 81: // OpInfix.
                    case 82: // OpInfix.
                    case 83: // OpInfix.
                    case 84: // OpInfix.
                    case 85: // OpInfix.
                    case 86: // OpInfix.
                    case 87: // OpInfix.
                    case 88: // OpInfix.
                    case 89: // OpInfix.
                    case 90: // OpInfix.
                    case 91: // OpInfix.
                    case 92: // OpInfix.
                    case 93: // OpInfix.
                    case 94: // OpInfix.
                    case 95: // OpInfix.
                    case 96: // OpInfix.
                    case 97: // OpInfix.
                    case 98: // OpInfix.
                    case 99: // OpInfix.
                    case 100: // OpInfix.
                    case 101: // OpInfix.
                        $term = new MP_Operation($term1->value, $term0, $term2);
                        $term->position = ['start' => $term0->position['start'], 'start-col' => $term0->position['start-col'], 'start-row' => $term0->position['start-row'], 'end' => $term2->position['end']];
                        break;
                    case 76: // OpInfix.
                        $term = new MP_Operation($term1->value, $term0, $term2);
                        $term->position = ['start' => $term0->position['start'], 'start-col' => $term0->position['start-col'], 'start-row' => $term0->position['start-row'], 'end' => $term2->position['end']];
                        if ($term1->note !== null) {
                            $term->position[$term1->note === 'inserted with whitespace' ? 'fixspaces' : 'insertstars'] = true;
                        }
                        break;
                    case 102: // Abs.
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
                    $root = $stack[1];
                    if ($collectcomments === true) {
                        // For now we simply append the comments withotu any
                        // sensible interleaving. One can always doe extra work
                        // to find the MP-objects that have positions that cover
                        // those comments and move them to correct places.
                        $root->items = array_merge($root->items, $commentdump);
                    }
                    return $root;
                }
                
                // Where to next?
                $stack[] = $goto[$stack[count($stack)-2]][$action[3]];

                // After reduce we need to track whitespace again.
                $whitespaceseen = false;
            }
        }

        // The result should be on the top of the stack.
        $root = end($stack);

        if ($collectcomments) {
            // For now we simply append the comments withotu any
            // sensible interleaving. One can always doe extra work
            // to find the MP-objects that have positions that cover
            // those comments and move them to correct places.
            $root->items = array_merge($root->items, $commentdump);
        }

        return $root;
    }

}
