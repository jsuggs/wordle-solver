# Wordle Solver

Due to peer ridicule, my first hacks were subpar.  Bringing in symfony.  More to come, not sure about namespaces, etc.

## Goals
- Be able to solve all words
- Use the simplest algorithms possible
- Learn something new and have fun doing it
- Create a UI for helping to solve where you are

## Words
I'm going to start with this list I found here.  I'm going to use this as a separate dataset for frequency
https://www-cs-faculty.stanford.edu/~knuth/sgb-words.txt

Here's the word list as the wordle archive says
https://github.com/DevangThakkar/wordle_archive/blob/master/src/data/words.js

## Algorithm notes
Starting to do some unscientific testing with how I pick my algorithm (hopefully more to come on that soon).  I'm now at a point where I have some different strategies that I can use, so deciding when to use them is kinda fun.

## Setup
TODO
- clone
- composer install
- bin/reset_db
- symfony server:start (todo, not portable)

## Random Thoughts
Wordle can seem hard, but kinda like Deep Blue (note, I am *NOT* trying to say solving wordle is even remotely as compplicated as chess), once you get a grasp of the domain, it's not that bad, its just starting to put together ideas and have a means of testing them.

I'm pretty certain I can take this further, and I may.  Given the fact that the state space is small enough and static, the "solution" can become objective.  I can see this going in the way optimization, both in the efficiency of algorithm, but also in code.  My first passes are just a progression of getting something working, mixed in with some "ideas" but then caught up with "lets just get something working".

Finally, I hope that this helps to document a bit about how I go about problem solving.  I may not be the smatest, or have the most efficient algorithms.  However, I like to attack a problem, even if its weird or wrong or imprecise.  Then I refine it.

Looks like I've got a good foundation.  Most are found under 6, but a few take 7.  Going to start looking into the data a bit more.

Frequency of a word is useful in the context of how we utilize words.  However, in the context of "solving" wordle it has no merit.  The algorithm(s) should not take into account usage (or in a weird way, emotion), but rather just facts.