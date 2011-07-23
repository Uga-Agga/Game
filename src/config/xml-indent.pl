#!/usr/bin/perl -w
#
# xml-indent.pl - re-indent an XML document
# Copyright (c) 2002  Elmar Ludwig
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License as
# published by the Free Software Foundation; either version 2 of
# the License, or (at your option) any later version.

$level = 0;

while (<>)
{
    s/^\s+//;
    s/\s+$//;
    $shift = 0;
    ++$shift while /<\w[^\/>]*>/g;
    --$shift while /<\/\w[^>]*>/g;

    $level += $shift if $shift < 0;
#   print "\t" x (2*$level/8) . ' ' x (2*$level%8) . "$_\n";
    print ' ' x (2*$level) . "$_\n";
    $level += $shift if $shift > 0;
}
