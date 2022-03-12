# Wordle Solver

First, why am I buidling a wordle solver?  Mainly to see if I can.  Also, my family is going to be in Texas for a week, so I've got some extra time on my hands.

## Assumptions / Methodology
I'm going to use some basic php (its what I know best) and I think at least to start, I'm going to focus on getting something working in more or a brute force type of situation.  Then I'll layer in more advanved techniques as it progresses.

I'm going to start with a common 5 letter word list.  The top X common words, ordered by usage (or something measurable).  I'll break that out into its characters and I can start to use that as the basis for simple inclusions and exclusions.

Based on the progression of the choices, I'll use an algorithm that decides what "strategy" should be used.  First thought is that each word will have some metrics/statistics (TBD) that can be attributed to it.  As the techniques get more advanced, I'll see where that leads...but start simple.

## Words
I'm going to start with this list I found here
https://www-cs-faculty.stanford.edu/~knuth/sgb-words.txt