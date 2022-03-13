# Wordle Solver

First, why am I buidling a wordle solver?  Mainly to see if I can.  Also, my family is going to be in Texas for a week, so I've got some extra time on my hands.

## Assumptions / Methodology
I'm going to use some basic php (its what I know best) and I think at least to start, I'm going to focus on getting something working in more or a brute force type of situation.  Then I'll layer in more advanved techniques as it progresses.

I'm going to start with a common 5 letter word list.  The top X common words, ordered by usage (or something measurable).  I'll break that out into its characters and I can start to use that as the basis for simple inclusions and exclusions.

Based on the progression of the choices, I'll use an algorithm that decides what "strategy" should be used.  First thought is that each word will have some metrics/statistics (TBD) that can be attributed to it.  As the techniques get more advanced, I'll see where that leads...but start simple.

## Words
I'm going to start with this list I found here.  I'm going to use this as a separate dataset for frequency
https://www-cs-faculty.stanford.edu/~knuth/sgb-words.txt

Here's the word list as the wordle archive says
https://github.com/DevangThakkar/wordle_archive/blob/master/src/data/words.js

## Algorithm notes
Ok, basic handling of correct and not found letters.  Now its time to put in the wrong location logic.
Now, need to make sure that if letters are in the wrong place, that we limit to words that have those letters.


## Day Two
Going to focus on test, then maybe a larger dataset of words.  Finally, starting to look into some stats if time permits.  Bonus work may be to build a UI to help test things out.

I've got all 5 letter words in the database, so check.  I'm taking some shortcuts, but I have a slightly better way of looking at what strategy to take (still no numbers/facts to show use, just some hard coded stuff based on gut/testing)


## Setup
Not at a place to really share this at the moment.  But what I've got now relies on PHP7 with SQLITE3 extensions being installed.  Goal is to streamline this a bit more, potentially utilize some more collaborative choices.

## Random Thoughts
Wordle can seem hard, but kinda like Deep Blue (note, I am *NOT* trying to say solving wordle is even remotely as compplicated as chess), once you get a grasp of the domain, it's not that bad, its just starting to put together ideas and have a means of testing them.

I'm pretty certain I can take this further, and I may.  Given the fact that the state space is small enough and static, the "solution" can become objective.  I can see this going in the way optimization, both in the efficiency of algorithm, but also in code.  My first passes are just a progression of getting something working, mixed in with some "ideas" but then caught up with "lets just get something working".

Finally, I hope that this helps to document a bit about how I go about problem solving.  I may not be the smatest, or have the most efficient algorithms.  However, I like to attack a problem, even if its weird or wrong or imprecise.  Then I refine it.

Looks like I've got a good foundation.  Most are found under 6, but a few take 7.  Going to start looking into the data a bit more.