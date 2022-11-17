# Writing worked solutions in STACK

There is something of an art to writing worked solutions in STACK which are robust to different random versions.  Creating a worked solution, in this example and more generally, uses the following basic ideas.

1. We should start with the worked solution and work backwards to the question.  
2. Technically, we should solve as many mathematical problems as possible in Maxima, with `simp:false`, and not try to solve mathematical problems at the LaTeX level.  This means we will be simplifying _parts_ of expressions explicitly using Maxima code `ev( ... , simp)` within larger expressions.  The advantage of working at the mathematical level is that Maxima will display negative values correctly and not as, e.g., \( (x+ -3)^2 - 2^2 \).
3. We should solve as many display problems as possible in the castext level, especially those involving the relationship of text to mathematics.
4. Steps can be ommited in the worked solution, or conditional statements added to the worked solution, using the [question blocks](Question_blocks.md) functionality.  The castext is the right place to deal with formatting, not within the question variables.

If your STACK version is older than 20221010 then you will need to add this function to the question variables.

```
texdisp_select(ex) := sconcat("\\color{red}{\\underline{", tex1(first(args(ex))), "}}");
texput(disp_select, texdisp_select);
```

## Solving a linar equation.


This question is to "solve \( 5(x-3) = 4(x-3) + 2\)''.  In this example there is no randomisation, although this would be relatively easy to add later.


```
/* These functions add lines to the argument. (Part of core STACK?)     */
/* argument_step(existing_argument, string_instruction, maxima_result)  */
argument_step([ex]) := block([arg1],
  arg1:first(ex),
  append(arg1, [[second(ex), third(ex)]])  
);
argument_add([ex]) := block([arg1],
  arg1:first(ex),
  append(arg1, [[sconcat("Add ", stack_disp(second(ex) ,"i"), " to both sides."), lhs(second(last(arg1)))+second(ex)=rhs(second(last(arg1)))+second(ex)]])  
);

/* This will hold the complete argument. */
q0:5*(x-3) = 4*(x-3)+2;
ar1:[["Solve", q0]];
ar1:argument_add(ar1,-4*(x-3));
ar1:argument_step(ar1,"Gather like terms", ev(second(last(ar1)),simp) );
ar1:argument_add(ar1,3);
ar1:argument_step(ar1,"Perform integer arithmetic", ev(second(last(ar1)),simp) );
```

Then to display the argument use the following question blocks.

```
[[ foreach n='ev(makelist(k,k,1,length(ar1)),simp)' ]] 
    {@first(ar1[n])@} \[{@second(ar1[n])@}\] 
[[/ foreach ]]
```

Notes:

1. Using lists for the whole argument make it easier to add or remove lines, and to control which lines are shown.  There are fewer variable names to keep track of.
2. The imperative can only contain simple strings, and not full castext.  So creating imperatives which themselves contain mathematical expressions based on variables is more difficult.  That's what castext is for!
3. You can refer to the previous line in the argument with the `last` command, e.g. to access the Maxiam expression in the previous step use `second(last(ar1))`.
4. The imperative must be a simple strings, and not full castext.  Creating imperatives which themselves contain mathematical expressions based on variables is more difficult.  That's what castext is for!  See the `argument_add` function for an example of how to do this.

TODO: decide how to _not_ display particular steps in this calculation.  In particular the instruction "add 3 to both sides" should also simplify the result.  So we should display the _instruction_ to step 3, but display the _result_ of step 4!

## Two-column proof

We can use the question blocks functionality to create a two column proof.  The last part of the above argument can be printed out by looping over the lists we created, testing whether to display each line.

```
<table>
[[ foreach n='ev(makelist(k,k,1,length(ar1)),simp)' ]] 
    <tr>
      <td> {@first(ar1[n])@} </td>
      <td> \({@second(ar1[n])@}\) </td>
    </tr>
[[/ foreach ]]
</table>
```

High-level display choices such as selecting a two-column table over prose is best solved at the castext level, not within Maxima.

To line up equality signs in the display, assuming each element is an equation, we can add an extra column.

```
<table>
[[ foreach n='ev(makelist(k,k,1,length(ar1)),simp)' ]] 
    <tr>
      <td> {@first(ar1[n])@} </td>
      <td align="right"> \({@lhs(second(ar1[n]))@}\) </td>
      <td align="left"> \(= {@rhs(second(ar1[n]))@}\) </td>
    </tr>
[[/ foreach ]]
</table>
```

## HTML details/summary

Detail within a particular worked solution can be shown, at the student's discression, using the HTML details/summary tags.

```
Expand out the quadratic 
  \[(x+3)(x+5)\]
<details>
  <summary>(details)</summary>
  \[ = x(x+5)+3(x+5)\]
  \[ = x^2+5x+3x+3\times 5 \]
</details>
  \[ = x^2+8x+15 \]
```

Expand out the quadratic 
  \[(x+3)(x+5)\]
<details>
  <summary>(details)</summary>
  \[ = x(x+5)+3(x+5)\]
  \[ = x^2+5x+3x+3\times 5 \]
</details>
  \[ = x^2+8x+15 \]

## Solving a quadratic equation via completed squares and difference of two squares.

This example gives details of solving a quadratic equation via completed squares and difference of two squares.  We start with numbers \(a\), \(n_1\) and \(n_2\) and expand out \(a(x-n_1)(x-n_2)\) to keep careful control over the roots and the coefficient of \(x^2\).  In the example below the presentation is kept very simple.  Ultimately, some better styling (CSS) would significantly improve the presentation, perhaps using a two-column layout.

The following is the question variables field.

```
/* Control the coeffient of x^2 and the roots. */
a1:1;
n1:-2;
n2:3;
/* Define the quadratic and monic quadratic from the roots. */
p0:ev(expand(a1*(x-n1)*(x-n2)), simp);
p1:ev(expand((x-n1)*(x-n2)), simp);
/* Coefficients of the polynomial.  */
c0:ev(coeff(p1,x,0),simp);
c1:ev(coeff(p1,x,1),simp);
c2:ev(coeff(p1,x,2),simp);
/* Calculations based on coefficients. */
n3:ev((c1/2)^2,simp);  /* b/2 */
n4:ev((c1/2)^2-c0,simp); /* b^2/4-c */
n5:ev(sqrt(n4),simp); 
n6:ev(sqrt(c2)*x,simp); /* We need this simplified, especially when c2=1. */

/* These are lines in the working, (p*) or other associated expressions (q*).  */
q0:ev(expand((x+c1/2)^2),simp);

p2:ev(p1-c0,simp) = ev(-1*c0,simp);
p3:ev(p1-c0,simp) + disp_select(n3) = disp_select(n3) - c0;

s4:"We may now factor the left hand side";
p4:disp_select((ev(x+c1/2,simp))^2) = n4;
c4:true;
s5:"Write the right hand side as a square";
p5:(ev(n6+c1/2,simp))^2 = disp_select(n5^2) ;
c5:true;
s6:"and subtract this from both sides.";
p6:(ev(n6+c1/2,simp))^2 - n5^2 = 0;
c6:true;
s7:"Now we have the difference of two squares.";
p7:(ev(n6+c1/2,simp)-n5)*(ev(n6+c1/2,simp)+n5) = 0;
c7:true;
s8:"Select the numbers in each factor."
p8:(n6+disp_select(ev(c1/2,simp)-n5))*(n6+disp_select(ev(c1/2,simp)+n5)) = 0;
c8:true;
s9:"and perform arithmetic.";
p9:(ev(n6+c1/2-n5,simp))*(ev(n6+c1/2+n5,simp)) = 0;
c9:integerp(ev(c1/2-n5,simp));
/* The correct answer. */
s10:"Which gives the final answer";
ta:x=n1 nounor x=n2;
c10:true;

/* Create lists of expressions. */
l1:[p4,p5,p6,p7,p8,p9,ta];
l0:ev(makelist(k,k,1,length(l1)),simp);
l2:[s4,s5,s6,s7,s8,s9,s10];
/* This list controls if each step is displayed or not. */
l3:[c4,c5,c6,c7,c8,c9,c10];
```


In the Options turn the Question-level simplify to `no`.

The point of this example is the general feedback, i.e. the worked solution, not the whole question.

```
Solve \({@p0@}=0\).
[[ if test='is(a1=1)' ]]
Since the coefficient of the highest power, \(x^2\), equals one, we have what is known as a "monic" polynomial which we can start to solve.
[[ else ]]
The first step is to divide through by the coefficient of the highest power, \(x^2\), so we have what is known as a "monic" polynomial where the coefficient of the highest power, \(x^2\), equals one.  Doing this, we now have to solve \({@p1@}=0\).
[[/ if ]]
Assume \(b\) is the coefficient of \(x\), which in this case is {@c1@}. Divide this by \(2\), and consider \( (x+b/2)^2 = {@ (ev(sqrt(c2)*x,simp)+c1/2)^2 = q0@} \).  We use this as follows.
\[ {@p1=0@} \] 
[[ if test='is(c0#0)' ]]
Subtract the constant term from both sides.
\[ {@p2@} \] 
[[/ if ]]
Add  \(b^2/4\) to both sides
\[ {@p3@} \]
and add the numerical terms on the right hand side.  Now is the time to use \(  {@ (ev(sqrt(c2)*x,simp)+c1/2)^2 = q0@} \) and notice the calculation so far makes the left side a perfect square.

[[ foreach x='l0' ]] 
[[ if test='l3[x]' ]]
{@l2[x]@} 
\[{@l1[x]@}\] 
[[/ if]]
[[/ foreach ]]
```

This particular worked solution will create a reasonable step-by-step solution in all the following cases:

1. Roots integer and distinct.
2. One root is zero.  Requires one "if" statement as a question block to suppress "add constant term to both sides".
3. \(a \neq 1\). Requires one "if" statement as a question block, to divide through by \(a\) at the start.
4. Roots contain a surd.  Requires one "if" statement as a question block, to suppress simplification of numbers which can't be added and simplified.
5. Roots are Gaussian integers.
6. Roots are complex conjugate.

There are many ways to solve quadratics, but this method has been selected for the following reasons.

* This "works" for all quadracits. Therefore if introduced early the method generalises beyond the special case of integer roots.
* This method makes use of the completed square and difference of two squares, themselves both important topics.
* This method involves "appreciation of form", in particular "can we make this a perfect square?", which is an important theme in algebraic manipulation.  This is a general concept in elementary algebra.

However, this method does _not_ work well with repeated integer roots. Hence, repeated roots is arguably better assessed with dedicated questions assessing the single issue explicitly.  Similarly, this worked solution does not work well with the dfference of two squares, i.e. \( x^2-c^2=(x-c)(x+c) \). Both perfect squares, and the differece of two squares, _could_ be accommodated with more question blocks.  However, invariance of the steps in the worked should is arguably a good test of when questions are the same or different, for a particular student group.  The orginal goal was to write a single STACK question with a worked solution which is robust in a variety of situations.  The attempt to write a general worked solution, and the above analysis of the general case,  has suggested the following didactic sequence.

1. Perfect squares: \( (x+c)^2 = x^2+2cx+c^2 \).
2. Difference of two squares: \( (x+c)(x-c) = x^2-c^2 \).
3. The general case, solved by using both of the above.

Within this basic idea of invariance, some special cases of the general quadratic \( ax^2+bx+c=0\), e.g. \(a=1\) or \(c=0\) merely omit one or more of the steps in the general worked solution.  For example, if \(c=0\) then it makes no sense to have a step "Subtract the constant term from both sides."  This special cases does not really lead to genurinly new cases in the worked solution, we just need to omit a particular step which is trivial in this example.  These sub-cases could be conciously used to create progressivly more complex cases, even within the relm of quadratics with integer roots.

This worked solution does work even in the case \(a \neq 1\).  For example \({3\,x^2-x-2}=0\) has roots \(-2/3\) and \(1\), and the worked solution above gives a reasonable solution with the following values.

```
a1:3;
n1:-2/3;
n2:1;
```

Notice this method has conciously avoided taking the square roots of both sides of an equation and hence entirely side-stepped the confusing issue of how to deal with \( \pm \).  Avoiding taking the square roots of both sides of an equation does not lead to the shortest worked solution in all cases.

This method completely side-steps factoring with a "guess and check" method, even though this is widley taught and quicker when mastered.


## Creating a single "array" to hold most of the argument.

```
/* Control the coeffient of x^2 and the roots. */
a1:1;
n1:-2;
n2:3;
/* Define the quadratic and monic quadratic from the roots. */
p0:ev(expand(a1*(x-n1)*(x-n2)), simp);
p1:ev(expand((x-n1)*(x-n2)), simp);
/* Coefficients of the polynomial.  */
c0:ev(coeff(p1,x,0),simp);
c1:ev(coeff(p1,x,1),simp);
c2:ev(coeff(p1,x,2),simp);
/* Calculations based on coefficients. */
n3:ev((c1/2)^2,simp);  /* b/2 */
n4:ev((c1/2)^2-c0,simp); /* b^2/4-c */
n5:ev(sqrt(n4),simp); 
n6:ev(sqrt(c2)*x,simp); /* We need this simplified, especially when c2=1. */

/* These are lines in the working, (p*) or other associated expressions (q*).  */
q0:ev(expand((x+c1/2)^2),simp);

p2:ev(p1-c0,simp) = ev(-1*c0,simp);
p3:ev(p1-c0,simp) + disp_select(n3) = disp_select(n3) - c0;

/* This will hold the complete argument. */
ar1:[];

/* argument_step(existing_argument, string_instruction, maxima_result)  */
argument_step([ex]) := block([arg1],
  arg1:first(ex),
  append(arg1, [[second(ex), third(ex)]])  
);

/* Each line has an imperative (what we are about to do) and the result. */
ar1:argument_step(ar1,"We may now factor the left hand side", disp_select((ev(x+c1/2,simp))^2) = n4);
ar1:argument_step(ar1,"Write the right hand side as a square", (ev(n6+c1/2,simp))^2 = disp_select(n5^2));
ar1:argument_step(ar1,"and subtract this from both sides.", (ev(n6+c1/2,simp))^2 - n5^2 = 0);
ar1:argument_step(ar1,"Now we have the difference of two squares.", (ev(n6+c1/2,simp)-n5)*(ev(n6+c1/2,simp)+n5) = 0);
ar1:argument_step(ar1,"Select the numbers in each factor.", (n6+disp_select(ev(c1/2,simp)-n5))*(n6+disp_select(ev(c1/2,simp)+n5)) = 0);
/* Note, this line of the argument is conditional. */
if integerp(ev(c1/2-n5,simp)) then ar1:argument_step(ar1,"and perform arithmetic.", (ev(n6+c1/2-n5,simp))*(ev(n6+c1/2+n5,simp)) = 0);
ar1:argument_step(ar1,"which gives the result", x=n1 nounor x=n2);
```

Then the end of the castext is the following loop, using question blocks, to display the test.

```
[[ foreach n='ev(makelist(k,k,1,length(ar1)),simp)' ]] 
    {@first(ar1[n])@} \[{@second(ar1[n])@}\] 
[[/ foreach ]]
```

## Legacy ideas (delete?)

Have a third argument to the list to control display.

```
/* This function adds lines to the argument. (Part of core STACK?) */
add_to_argument([ex]):=block([arg1,disp],
  disp:true,
  if length(ex)>3 then disp:fourth(ex),
  arg1:first(ex),
  append(arg1,[[second(ex), third(ex),disp]])  
);
```

Then display with if statements in the castext.

```
[[ foreach n='ev(makelist(k,k,1,length(ar1)),simp)' ]] 
  [[ if test='third(ar1[n])' ]]
    {@first(ar1[n])@} \[{@second(ar1[n])@}\] 
  [[/ if]]
[[/ foreach ]]
```

