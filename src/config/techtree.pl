#!/usr/bin/perl -w
#
# techtree.pl - Create tech tree from Uga-Agga config files
# Copyright (c) 2003  Elmar Ludwig
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License as
# published by the Free Software Foundation; either version 2 of
# the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

use Getopt::Long;

sub pushuniq (\@@)
{
    my ($array, @list) = @_;

    foreach $ref (@list)
    {
	push @$array, $ref unless grep $ref == $_, @$array;
    }
}

sub ascending (@)
{
    my @list = @_;
    my $last = -1;

    foreach (@list)
    {
	return 0 unless $last < $_;
	$last = $_;
    }

    return 1;
}

sub dependency ($$$)
{
    my ($id, $min, $max) = @_;

    $min =  0 unless defined $min;
    $max = -1 unless defined $max;
    return {'id', $id, 'min', $min, 'max', $max};
}

sub name ($)
{
    local ($_) = @_;

    return $numeric ? "$$_{'id'}:$$_{'name'}" : $$_{'name'};
}

sub techtree (@)
{
    foreach (@_)
    {
	print name($_);
	print " $$_{'max'}" if exists $$_{'max'};
	print ':';

	if (exists $$_{'techs'})
	{
	    print map ' '.name($techs[$$_{'id'}])." $$_{'min'}",
		      grep $$_{'min'} > 0, @{$$_{'techs'}};
	    print map ' !'.name($techs[$$_{'id'}]),
		      grep $$_{'max'} == 0, @{$$_{'techs'}} if $exclude;
	}

	if (exists $$_{'build'})
	{
	    my @list = map name($build[$$_{'id'}])." $$_{'min'}",
			   grep $$_{'min'} > 0, @{$$_{'build'}};

	    push @list, map '!'.name($build[$$_{'id'}]),
			    grep $$_{'max'} == 0, @{$$_{'build'}} if $exclude;
	    print ' [', join(' ', @list), ']' if @list > 0;
	}

	print "\n";
    }

    print "\n";
}

sub exclude_list ($)
{
    my ($elem) = @_;
    my @list = ();

    if (exists $$elem{'techs'})
    {
	pushuniq @list, map $techs[$$_{'id'}],
			    grep $$_{'max'} == 0, @{$$elem{'techs'}};
	pushuniq @list, map &exclude_list($techs[$$_{'id'}]),
			    grep $$_{'min'} > 0, @{$$elem{'techs'}};
    }

    if (exists $$elem{'build'})
    {
	pushuniq @list, map $build[$$_{'id'}],
			    grep $$_{'max'} == 0, @{$$elem{'build'}};
	pushuniq @list, map &exclude_list($build[$$_{'id'}]),
			    grep $$_{'min'} > 0, @{$$elem{'build'}};
    }

    return @list;
}

sub validate (@)
{
    foreach (@_)
    {
	my @list = ();
	my $missing;

	push @list, map $techs[$$_{'id'}], grep $$_{'max'} == 0, @{$$_{'techs'}}
	    if exists $$_{'techs'};
	push @list, map $build[$$_{'id'}], grep $$_{'max'} == 0, @{$$_{'build'}}
	    if exists $$_{'build'};

	foreach $ref (exclude_list($_))
	{
	    $missing .= ' !'.name($ref) unless grep $ref == $_, @list;
	}

	print name($_).":$missing\n" if defined $missing;
    }
}

sub ordering (@)
{
    foreach (@_)
    {
	my @tlist = map $$_{'id'}, @{$$_{'techs'}} if exists $$_{'techs'};
	my @blist = map $$_{'id'}, @{$$_{'build'}} if exists $$_{'build'};

	if (!ascending(@tlist) || !ascending(@blist))
	{
	    print name($_).':';
	    print ' ', join(' ', @tlist) if @tlist;
	    print ' [', join(' ', @blist), ']' if @blist;
	    print "\n";
	}
    }
}

	## ========================================================== ##

$use = "Usage: $0 [options] [file ...]\n".
       "Create tech tree from Uga-Agga config files.\n\n".
       "  -c, --check\t\tcheck transitive build exclusions\n".
       "  -n, --numeric\t\tshow tech ID number before each name\n".
       "  -x, --exclude\t\tinclude exclude conditions in output\n".
       "      --order\t\tcheck ordering of dependency lists\n".
       "  -h, --help\t\tdisplay this help text and exit\n";

Getopt::Long::Configure('posix_default', 'bundling', 'no_ignore_case');
GetOptions('check|c', \$check, 'numeric|n', \$numeric, 'exclude|x', \$exclude,
	   'order', \$order, 'help|h', \$help) or die $use;

print $use and exit if defined $help;

	## ========================================================== ##

while (<>)
{
    /^\s*<!--/ and $comment = 1;

    if ($comment)
    {
	/-->\s*$/ and $comment = 0;
	next;
    }

    /<(Building|DefenseSystem|Science|Unit) / and $item = {};
    /<wonder wonderID="(\d+)"/		and $item = {'id', $id = $1};
    /<(Building|DefenseSystem|Science|Unit)ID>(\d+)<\/\1ID>/ and
	$$item{'id'} = $id = $2;
    /<Name>([^,]*).*<\/Name>/i		and $$item{'name'} = $1;

    /<BuildingDep buildingID="(\d+)"( min="(-?\d+)")?( max="(-?\d+)")?/ and
	push @{$$item{'build'}}, dependency($1, $3, $5);
    /<ScienceDep scienceID="(\d+)"( (min|value)="(-?\d+)")?( max(Value)?="(-?\d+)")?/ and
	push @{$$item{'techs'}}, dependency($1, $4, $7);

    /<MaxDevelopmentLevel>(\d+)<\/MaxDevelopmentLevel>/ and
	$$item{'max'} = $1;

    /<\/Building>/			and $build[$id] = $item and undef $item;
    /<\/DefenseSystem>/			and $forts[$id] = $item and undef $item;
    /<\/Science>/			and $techs[$id] = $item and undef $item;
    /<\/Unit>/				and $units[$id] = $item and undef $item;
    /<\/wonder>/			and $magic[$id] = $item and undef $item;
}

if ($check)
{
    validate(@units, @forts, @build, @techs, @magic);
}
elsif ($order)
{
    ordering(@units, @forts, @build, @techs, @magic);
}
else
{
    techtree(@units);
    techtree(@forts);
    techtree(@build);
    techtree(@techs);
    techtree(@magic);
}
