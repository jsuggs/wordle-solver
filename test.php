<?php
require_once dirname(__FILE__).'/Database.php';
require_once dirname(__FILE__).'/Wordle.php';
// CONST WORDLE_ANSWERS = ["REBUT", "SISSY", "HUMPH", "AWAKE", "BLUSH", "FOCAL", "EVADE", "NAVAL", "SERVE", "HEATH", "DWARF", "MODEL", "KARMA", "STINK", "GRADE", "QUIET", "BENCH", "ABATE", "FEIGN", "MAJOR", "DEATH", "FRESH", "CRUST", "STOOL", "COLON", "ABASE", "MARRY", "REACT", "BATTY", "PRIDE", "FLOSS", "HELIX", "CROAK", "STAFF", "PAPER", "UNFED", "WHELP", "TRAWL", "OUTDO", "ADOBE", "CRAZY", "SOWER", "REPAY", "DIGIT", "CRATE", "CLUCK", "SPIKE", "MIMIC", "POUND", "MAXIM", "LINEN", "UNMET", "FLESH", "BOOBY", "FORTH", "FIRST", "STAND", "BELLY", "IVORY", "SEEDY", "PRINT", "YEARN", "DRAIN", "BRIBE", "STOUT", "PANEL", "CRASS", "FLUME", "OFFAL", "AGREE", "ERROR", "SWIRL", "ARGUE", "BLEED", "DELTA", "FLICK", "TOTEM", "WOOER", "FRONT", "SHRUB", "PARRY", "BIOME", "LAPEL", "START", "GREET", "GONER", "GOLEM", "LUSTY", "LOOPY", "ROUND", "AUDIT", "LYING", "GAMMA", "LABOR", "ISLET", "CIVIC", "FORGE", "CORNY", "MOULT", "BASIC", "SALAD", "AGATE", "SPICY", "SPRAY", "ESSAY", "FJORD", "SPEND", "KEBAB", "GUILD", "ABACK", "MOTOR", "ALONE", "HATCH", "HYPER", "THUMB", "DOWRY", "OUGHT", "BELCH", "DUTCH", "PILOT", "TWEED", "COMET", "JAUNT", "ENEMA", "STEED", "ABYSS", "GROWL", "FLING", "DOZEN", "BOOZY", "ERODE", "WORLD", "GOUGE", "CLICK", "BRIAR", "GREAT", "ALTAR", "PULPY", "BLURT", "COAST", "DUCHY", "GROIN", "FIXER", "GROUP", "ROGUE", "BADLY", "SMART", "PITHY", "GAUDY", "CHILL", "HERON", "VODKA", "FINER", "SURER", "RADIO", "ROUGE", "PERCH", "RETCH", "WROTE", "CLOCK", "TILDE", "STORE", "PROVE", "BRING", "SOLVE", "CHEAT", "GRIME", "EXULT", "USHER", "EPOCH", "TRIAD", "BREAK", "RHINO", "VIRAL", "CONIC", "MASSE", "SONIC", "VITAL", "TRACE", "USING", "PEACH", "CHAMP", "BATON", "BRAKE", "PLUCK", "CRAZE", "GRIPE", "WEARY", "PICKY", "ACUTE", "FERRY", "ASIDE", "TAPIR", "TROLL", "UNIFY", "REBUS", "BOOST", "TRUSS", "SIEGE", "TIGER", "BANAL", "SLUMP", "CRANK", "GORGE", "QUERY", "DRINK", "FAVOR", "ABBEY", "TANGY", "PANIC", "SOLAR", "SHIRE", "PROXY", "POINT", "ROBOT", "PRICK", "WINCE", "CRIMP", "KNOLL", "SUGAR", "WHACK", "MOUNT", "PERKY", "COULD", "WRUNG", "LIGHT", "THOSE", "MOIST", "SHARD", "PLEAT", "ALOFT", "SKILL", "ELDER", "FRAME", "HUMOR", "PAUSE", "ULCER", "ULTRA", "ROBIN", "CYNIC", "AROMA", "CAULK", "SHAKE", "DODGE", "SWILL", "TACIT", "OTHER", "THORN", "TROVE", "BLOKE", "VIVID", "SPILL", "CHANT", "CHOKE", "RUPEE", "NASTY", "MOURN", "AHEAD", "BRINE", "CLOTH", "HOARD", "SWEET", "MONTH", "LAPSE", "WATCH", "TODAY", "FOCUS", "SMELT", "TEASE", "CATER", "MOVIE", "SAUTE", "ALLOW", "RENEW", "THEIR", "SLOSH", "PURGE", "CHEST", "DEPOT", "EPOXY", "NYMPH", "FOUND", "SHALL", "HARRY", "STOVE", "LOWLY", "SNOUT", "TROPE", "FEWER", "SHAWL", "NATAL", "COMMA", "FORAY", "SCARE", "STAIR", "BLACK", "SQUAD", "ROYAL", "CHUNK", "MINCE", "SHAME", "CHEEK", "AMPLE", "FLAIR", "FOYER", "CARGO", "OXIDE", "PLANT", "OLIVE", "INERT", "ASKEW", "HEIST", "SHOWN", "ZESTY", "HASTY", "TRASH", "FELLA", "LARVA", "FORGO", "STORY", "HAIRY", "TRAIN", "HOMER", "BADGE", "MIDST", "CANNY", "FETUS", "BUTCH", "FARCE", "SLUNG", "TIPSY", "METAL", "YIELD", "DELVE", "BEING", "SCOUR", "GLASS", "GAMER", "SCRAP", "MONEY", "HINGE", "ALBUM", "VOUCH", "ASSET", "TIARA", "CREPT", "BAYOU", "ATOLL", "MANOR", "CREAK", "SHOWY", "PHASE", "FROTH", "DEPTH", "GLOOM", "FLOOD", "TRAIT", "GIRTH", "PIETY", "PAYER", "GOOSE", "FLOAT", "DONOR", "ATONE", "PRIMO", "APRON", "BLOWN", "CACAO", "LOSER", "INPUT", "GLOAT", "AWFUL", "BRINK", "SMITE", "BEADY", "RUSTY", "RETRO", "DROLL", "GAWKY", "HUTCH", "PINTO", "GAILY", "EGRET", "LILAC", "SEVER", "FIELD", "FLUFF", "HYDRO", "FLACK", "AGAPE", "VOICE", "STEAD", "STALK", "BERTH", "MADAM", "NIGHT", "BLAND", "LIVER", "WEDGE", "AUGUR", "ROOMY", "WACKY", "FLOCK", "ANGRY", "BOBBY", "TRITE", "APHID", "TRYST", "MIDGE", "POWER", "ELOPE", "CINCH", "MOTTO", "STOMP", "UPSET", "BLUFF", "CRAMP", "QUART", "COYLY", "YOUTH", "RHYME", "BUGGY", "ALIEN", "SMEAR", "UNFIT", "PATTY", "CLING", "GLEAN", "LABEL", "HUNKY", "KHAKI", "POKER", "GRUEL", "TWICE", "TWANG", "SHRUG", "TREAT", "UNLIT", "WASTE", "MERIT", "WOVEN", "OCTAL", "NEEDY", "CLOWN", "WIDOW", "IRONY", "RUDER", "GAUZE", "CHIEF", "ONSET", "PRIZE", "FUNGI", "CHARM", "GULLY", "INTER", "WHOOP", "TAUNT", "LEERY", "CLASS", "THEME", "LOFTY", "TIBIA", "BOOZE", "ALPHA", "THYME", "ECLAT", "DOUBT", "PARER", "CHUTE", "STICK", "TRICE", "ALIKE", "SOOTH", "RECAP", "SAINT", "LIEGE", "GLORY", "GRATE", "ADMIT", "BRISK", "SOGGY", "USURP", "SCALD", "SCORN", "LEAVE", "TWINE", "STING", "BOUGH", "MARSH", "SLOTH", "DANDY", "VIGOR", "HOWDY", "ENJOY"]


$wordsToTest = [
	"REBUT", "SISSY", "HUMPH", "AWAKE", "BLUSH", "FOCAL", "EVADE", "NAVAL", "SERVE", "HEATH", "DWARF", "MODEL"
];

class TestResult
{
	public function __construct(int $numGuesses, string $word, bool $solved, int $time) {
		$this->numGuesses = $numGuesses;
		$this->word = $word;
		$this->time = $time;
	}

	public function __toString() {
		//
	}
}

class Tester
{
	public const MAX_GUESSES = 6;
	private Solver $solver;

	public function __construct(Solver $solver)
	{
		$this->solver = $solver;
	}

	public function runTest(string $word) : TestResult
	{
		$guessNumber = 1;
		$results = [];
		$found = false;
		$startTime = time();

		while ($guessNumber <= self::MAX_GUESSES) {
			// Setup the wordle
			$wordle = new Wordle();
			$wordle->results = $results;

			$guess = $this->solver->solve($wordle);
			$result = ResultTester::getGuessResult($word, $guess->word);
			echo sprintf("Guess %d: %s Result: %s\n", $guessNumber, $guess->word, $result);

			if ($result->isCorrect()) {
				$found = true;
				break;
			}
			$results[] = $result;

			// Update the wordle with the result, try again

			$guessNumber++;
		}
		$endTime = time();

		$totalTime = $endTime - $startTime;

		return new TestResult($guessNumber, $word, $found, $totalTime);
	}
}

$db = new Database();
$solver = new Solver($db);
$tester = new Tester($solver);

echo "Starting Tests\n";
$testResults = [];
foreach ($wordsToTest as $word) {
	echo "Word: $word\n";
	$testResults[] = $tester->runTest($word);
}

var_dump($testResults);