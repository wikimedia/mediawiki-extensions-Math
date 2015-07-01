#! /usr/bin/perl
my $texvc = `texvc '\\sin(x)+{}{}\\cos(x)^2 newcommand'`;
if (substr($result,0,1) eq "+") {
	print "good";
} else {
	print "bad";
}
print $result;
my $ = `tex2svg '\\sin(x)+{}{}\\cos(x)^2 newcommand'`;
