#!/usr/bin/perl
#
# mysql_coldiff
# $Name: mysql_coldiff-1_0 $
# 	This tool allows one to compare the data in two tables to see
# 	which data in which rows differ between the two.
#
#	Copyright (C) 2003 Ross A. Beyer, rbeyer@rossbeyer.net
#
#	DataDiff 0.1.0 Copyright (C) 2002 Jon D. Frisby, jfrisby@mrjoy.com
#
#	Documentation about the specifics of this module can be
#	found by reading it's POD via "perldoc mysql_coldiff".
#
#	CVS $Id: mysql_coldiff,v 1.8 2003/09/26 23:45:35 rbeyer Exp $
#
#   License & Copyright Information
#   -------------------------------
#   'MySQL' is a Trademark of MySQL AB in the United States and other
#   countries, and is used according to the terms of the MySQL AB
#   Trademark Policy. A copy of this policy can be found at www.mysql.com.
#
#   This program is free software; you can redistribute it and/or
#	modify it under the terms of the GNU General Public License as published
#   by the Free Software Foundation; either version 2 of the License, or
#   (at your option) any later version.
#
#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program; if not, write to the Free Software
#   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#   or visit their website at www.gnu.org.
#

=head1 NAME

mysql_coldiff - This tool allows one to compare the data in two tables to see which data in which rows differ between the two.


=head1 SYNOPSIS

mysql_coldiff

=head1 DESCRIPTION

This program is designed to connect to a MySQL server and compare
the information in the two tables requested.  This program uses the
comparison operators in MySQL itself, but still may take some time
with large tables.  Similarly, the two databases should have
comparable structures.

If invoked without any arguments, a brief message will be displayed.
This program shows the differences in the data between two tables,
the <from_table> and the <to_table>.  In order to figure out how
to compare the two tables, a column in each table must be specified
as the <index column> to join on.

These two table arguments can be specified with the MySQL format
of 'database.table.index_column', where the 'index_column' element
is the column to join the two tables on.  These table arguments can
have one to three elements separated by periods.

If the database is not specified in the table argument, then the
default database specified by the -d option will be used.  Similarly,
if an index_column is not specified in the table argument, the value
of the -i option will be used.  If the -i option isn't specified,
then the program will try to use the index_column name from the
other table argument as the index_column name.  Similarly, values
specified in the table arguments override values specified by -d
or -i.

    -v  Displays the version number and exits.
    -d  Specifies the default database to be used if none is specified
            in the <from_table> or <to_table>.
    -i  Specifies which column to use to compare rows.  This will usually
			be a PRIMARY index, but need not be.
    -c  This argument can be used to compare columns with different names
            in the two tables, or to restrict the columns that are
            compared.  The <column_equivalences> string must be in
            a specific format.  Colons are used to separate to-from
            column pairs, and the equals sign is used to demarcate
            column names.  If the columns in the <from_table> are
            a, b, & c and the columns in the <to_table> are d, e, & f,
            the <column_equivalences> string would be a=d:b=e:c=f to
            compare <from_table>.a to <to_table>.d, etc.  Similarly,
            if we wanted to ignore the differences in the b and e columns,
            and only compare a to do and c to f, the <column_equivalences>
            string would be a=d:c=f, etc.  If no -c argument is given,
            the program will assume that the column names in <from_table>
            are the same as those in <to_table>, and compare them
            that way, ignoring any column name that is not present
            in both tables.

    -n  Ignore all DATE, TIME, DATETIME, and TIMESTAMP columns when
            comparing data.

    -u  This program will use <MySQL username> to connect to the MySQL
            server.
    -p  This program will use <MySQL password> as your password when
            connecting to the MySQL server.
    -h  This program will attempt to connect to the specified <MySQL host>
            server.

This Perl program requires the Perl modules listed in the "use"
statements (which may not be installed with your Perl distribution).

=cut

use strict;
use Getopt::Std;
use DBI;
use Term::ReadKey;

my $usage =
"mysql_coldiff Usage:

        mysql_coldiff
                   [-v]
                   [-d <database>]
                   [-i <index column>]
                   [-c <column_equivalences>]
                   [-n]

                   [-u <MySQL username>]
                   [-p <MySQL password>]
                   [-h <MySQL host>]

                   <from_table>
                   <to_table>

    For more information, please look at this program's documentation
    by typing 'perldoc mysql_coldiff'.
";


# Good, parse the switches
my %option = ();
unless( getopts ('vd:i:c:nu:p:h:', \%option) ) {die "$usage\n"; }

if( $option{v} )
	{
	my $name = ('$Name: mysql_coldiff-1_0 $');
	$name =~ s/\$//g;
	$name =~ s/Name: //g;
	print "$name, Copyright (C) 2003 Ross A. Beyer
mysql_coldiff comes with ABSOLUTELY NO WARRANTY.  This is free software,
under the terms of the GNU General Public License, please see
http://www.gnu.org/licenses/licenses.html#GPL for details.\n";
	exit(0);
	}

# Did we get our two tables?
my $from_table_in	= shift @ARGV;
my $to_table_in		= shift @ARGV;

unless( $from_table_in and $to_table_in )
	{
	die "You must specify both the <from_table> and the <to_table>\n$usage\n";
	}

# Did we get other stuff?  If so, the user was probably confused.
if( @ARGV )
	{
	die
	"We got some arguments that we don't know what to do with: @ARGV\n$usage\n";
	}


# Make sure that we have a complete database.table.column specification

my $default_db;
if( $option{d} )
	{
	$default_db = $option{d};
	}

my $index_column;
if( $option{i} )
	{
	$index_column = $option{i};
	}

my( $from_db, $from_table, $from_idx, $to_db, $to_table, $to_idx );

my( $error_message, $db_out, $table_out, $idx_out )
= check_table_spec( $from_table_in, "from_table", $default_db, $index_column );

if( $error_message )
	{
	die "$error_message\n$usage\n";
	}
else
	{
	$from_db	= $db_out;
	$from_table = $table_out;
	$from_idx	= $idx_out;
	}

( $error_message, $db_out, $table_out, $idx_out )
= check_table_spec( $to_table_in, "to_table", $default_db, $index_column );

if( $error_message )
	{
	die "$error_message\n$usage\n";
	}
else
	{
	$to_db		= $db_out;
	$to_table	= $table_out;
	$to_idx		= $idx_out;
	}

# At this point, the $from_idx and $to_idx may not be specified, so let's
# check.

unless( $from_idx and $to_idx )
	{
	die "You need to specify index columns.";
	}
if( $from_idx and !($to_idx)   ) { $to_idx   = $from_idx; }
if( $to_idx   and !($from_idx) ) { $from_idx = $to_idx;   }

# Let the user know what we're doing:
print "We're comparing the $from_db.$from_table using the $from_idx column and the $to_db.$to_table using the $to_idx column.\n\n";

# Before we connect to the server, we'll also parse the <column_equivalences>
# string for info.
my %column_equiv;

if( $option{c} )
	{
	my @pairs = split(/:/, $option{c});
	foreach my $pair (@pairs)
		{
		my ($from, $to);
		($from, $to) = split(/=/, $pair);
		$column_equiv{$to} = $from;
		}
	}


# All other options require connection to the database.

my( $db_host, $db_user, $db_password );

# If you are using this program on a frequent basis, you may
#   wish to uncomment and fill out the following lines.
#   if you place your MySQL password in this program, be
#   mindful of the security risk that implies.  A reasonable
#   solution is to remove any group or others permissions this
#   file might have (man chmod for details).
#
#   Additionally, due to the unless statements below, you can
#   do any combination of commenting or uncommenting of the
#   following lines, and the program will ask you for the
#   ones it doesn't have.
$db_host       = "";
#$db_user       = "";
#$db_password   = "";

# Set variables if given on the command line.
if( $option{u} )
	{
	$db_user = $option{u};
	}
if( $option{p} )
	{
	$db_password = $option{p};
	}
if( $option{h} )
	{
	$db_host = $option{h};
	}

# Explicitly ask the user, if we don't have these variables yet.
unless( $db_host )
    {
    print "MySQL Database host to connect to: ";
    $db_host = <STDIN>;
    chomp $db_host;
    }
unless( $db_user )
    {
    my $login = getlogin;
    print "MySQL Database user to connect as [$login]: ";
    $db_user = <STDIN>;
    chomp $db_user;
    unless ($db_user =~ /\w+/)
        {
        $db_user = undef;
        }
    }
unless( $db_password )
    {
    print "MySQL Database Password: ";
    ReadMode('noecho');
    $db_password = ReadLine(0);
    chomp $db_password;
    ReadMode('normal');

    unless ($db_password =~ /\w+/)
        {
        $db_password = undef;
        }
    }

my $dsn = "DBI:mysql:$to_db:$db_host";

# Connect to the database
my $dbh = DBI->connect ($dsn, $db_user, $db_password, {RaiseError => 1});

# Determine if the two tables are missing any rows, and report that.

# Find out which key elements in the to_table are missing in the from_table:
my $missing_message = missing_from_table($dbh, $from_db, $from_table, $from_idx, $to_db, $to_table, $to_idx);

print "\n$missing_message\n";


# Find out which key elements in the from_table are missing in the to_table:
$missing_message = missing_from_table($dbh, $to_db, $to_table, $to_idx, $from_db, $from_table, $from_idx);

print "$missing_message\n";


# If we don't have any equivalent columns, then figure out what the two
# tables have in common
unless( %column_equiv )
	{
	my( @from_columns, @to_columns );
	@from_columns	= columns_in_table( $dbh, "$from_db.$from_table" );
	@to_columns		= columns_in_table( $dbh, "$to_db.$to_table" );

	# We now want to find the intersection of these two sets
	my( %in_from, %intersection );
	foreach my $col (@from_columns) { $in_from{$col} = 1 }

	foreach my $col (@to_columns)
		{
    	if( $in_from{$col} )
			{ $intersection{$col} = 1 }
		}

	unless( %intersection )
		{
		$dbh->disconnect();
		print "There are no columns with the same name in <from_table> and <to_table>.\n";
		exit(0);
		}

	foreach my $col (keys %intersection)
		{
		$column_equiv{$col} = $col;
		}
	}

# Enforce the -n, notime option
if( $option{n} )
	{
	foreach my $col (keys %column_equiv)
		{
		# Find out if there are any time columns in <from_table>
		my $notime_type
		= time_type( $dbh, "$from_db.$from_table", $column_equiv{$col} );

		# Avoid looking into the <to_table>, if possible.
		if( $notime_type )
			{
			delete $column_equiv{$col};
			next;
			}

		$notime_type
		= time_type( $dbh, "$to_db.$to_table", $col );

		if( $notime_type )
			{
			delete $column_equiv{$col};
			}
		}
	}

print "We are comparing the following columns:\n";
foreach my $col (keys %column_equiv)
	{
	print "$column_equiv{$col} to $col\n";
	}

# Now we do the tricky work of comparing the elements row by row.
#
# First off get the list of valid common indexes.

my @indexes = get_common_indexes($dbh, $to_db, $to_table, $to_idx, $from_db, $from_table, $from_idx);


# Now we need to prepare a few things before we start the loop.
# We need to construct a series of MySQL IF statements
my $if_statements;
my @ifs;
foreach my $col (keys %column_equiv)
	{
	push @ifs, qq/
	IF( $from_db.$from_table.$column_equiv{$col} <=> $to_db.$to_table.$col,
		0,"$col" ) AS diff_$col/;
	}
$if_statements = join(",", @ifs);

my $diff_string =
qq{
SELECT $if_statements
			FROM $from_db.$from_table, $to_db.$to_table
			WHERE $from_db.$from_table.$from_idx = $to_db.$to_table.$to_idx
				AND $from_db.$from_table.$from_idx = ?
};


# Prepare the query for the diffing.
my $diff_sth = $dbh->prepare( $diff_string );


foreach my $idx (@indexes)
	{
	# First find out if there are any differences
	$diff_sth->execute( $idx );
	my @different_columns;
	my @row;
	while( @row = $diff_sth->fetchrow_array() )
		{
		foreach my $element (@row)
			{
			if( $element eq "0" ) { next; }
			push @different_columns, $element;
			}
		}

	unless( @different_columns ) { next; }

	my @from_diff_cols;
	foreach my $col (@different_columns)
		{
		push @from_diff_cols, $column_equiv{$col};
		}

	my $from_values_ref  = get_diffs(
									$dbh,
									$from_db,
									$from_table,
									$from_idx,
									$idx,
									@from_diff_cols
									);
	my $to_values_ref    = get_diffs(
									$dbh,
									$to_db,
									$to_table,
									$to_idx,
									$idx,
									@different_columns
									);


	my $diff_message =  diff_message(
									$from_idx,
									$to_idx,
									$idx,
									$from_values_ref,
									$to_values_ref,
									\%column_equiv,
									@different_columns
									);

	print "\n$diff_message\n";
	}

$diff_sth ->finish();


$dbh->disconnect();
exit(0);



########################## Subroutines ###################################


# This subroutine checks that the input strings for the table
# specification are in complete database.table form.
#
sub check_table_spec
	{
	my $table		= shift;
	my $table_name	= shift;
	my $default_db	= shift;
	my $default_col	= shift;

	my $error_message;
	my $db_out		= $default_db;
	my $table_out;
	my $col_out		= $default_col;

	if( $table =~ /\./gi )
		{
		my @elements = split(/\./, $table);
		if( $#elements > 2 )
			{
			$error_message = "Your specification for the <$table_name>, $table, has too many elements.";
			}
		elsif( $#elements == 2 )
			{
			($db_out, $table_out, $col_out) = @elements;
			}
		else
			{
			# We need to figure out what to do with only two elements.

			if( $default_db and $default_col )
				{
				$error_message = "Both -d and -c have been given, so <$table_name> must either have one element, or three, I can't make sense of two."
				}
			elsif( $default_db )
				{
				($table_out, $col_out) = @elements;
				}
			else
				{
				($db_out, $table_out) = @elements;
				}
			}
		}
	else
		{
		$table_out = $table;
		}

	unless( $db_out )
		{
		# We don't know which database to use.
		$error_message =
		"Please specifiy a database in <$table_name> or with -d.";
		}

	return( $error_message, $db_out, $table_out, $col_out );
	}


# This subroutine finds out which key elements in table1 are missing in table2
#
sub missing_from_table
	{
	my $dbh			= shift;
	my $one_db		= shift;
	my $one_table	= shift;
	my $one_idx		= shift;
	my $two_db		= shift;
	my $two_table	= shift;
	my $two_idx		= shift;

	my $message;

	my $missing_sth = $dbh->prepare
		(
		qq{
			SELECT $two_db.$two_table.$two_idx
			FROM $two_db.$two_table LEFT JOIN $one_db.$one_table
			ON $two_db.$two_table.$two_idx = $one_db.$one_table.$one_idx
			WHERE $one_db.$one_table.$one_idx is NULL
			}
		);

	my @missing_vals;
	$missing_sth->execute();
	while( my @row = $missing_sth->fetchrow_array() )
		{
		my ($temp_val) = @row;
		push @missing_vals, $temp_val;
		}
	$missing_sth->finish();

	if( @missing_vals )
		{
		$message = "The following are values of $two_db.$two_table.$two_idx that are missing from $one_db.$one_table.$one_idx :\n@missing_vals";
		}
	else
		{
		$message = "There are no values of $one_db.$one_table.$one_idx missing from $two_db.$two_table.$two_idx.";
		}

	return $message;
	}

# This subroutine finds out which indexes are both in table1 and table2
#
sub get_common_indexes
	{
	my $dbh			= shift;
	my $one_db		= shift;
	my $one_table	= shift;
	my $one_idx		= shift;
	my $two_db		= shift;
	my $two_table	= shift;
	my $two_idx		= shift;

	my @indexes;

	my $indexes_sth = $dbh->prepare
		(
		qq{
			SELECT $one_db.$one_table.$one_idx
			FROM $two_db.$two_table, $one_db.$one_table
			WHERE $one_db.$one_table.$one_idx = $two_db.$two_table.$two_idx
			}
		);

	$indexes_sth->execute();
	while( my @row = $indexes_sth->fetchrow_array() )
		{
		my ($temp_val) = @row;
		push @indexes, $temp_val;
		}
	$indexes_sth->finish();

	return @indexes;
	}


# This subroutine returns a list of the columns in a given table.
# The second argument should either be a complete database.table
# string, or the $dbh should be pointing at the right database
# if only a table name is given.
sub columns_in_table
	{
	my $dbh			= shift;
	my $db_table	= shift;

	my @columns;

	my $show_columns_sth = $dbh->prepare
		(
		qq{
			SHOW COLUMNS FROM $db_table
			}
		);

	$show_columns_sth->execute();
	while( my @row = $show_columns_sth->fetchrow_array() )
		{
		my ($temp_col) = @row;
		push @columns, $temp_col;
		}
	$show_columns_sth->finish();

	return @columns;
	}


# This subroutine determines if the given column from the given table
# is of a particular time type, and if so, returns it.
sub time_type
	{
	my $dbh		= shift;
	my $table	= shift;
	my $column	= shift;

	my $time_type;

	my $type_sth = $dbh->prepare(
						qq{
							DESCRIBE $table $column
							}
						);
	$type_sth->execute();

	my $notime_type;
	while( my @row = $type_sth->fetchrow_array() )
		{
		my( $name, $type ) = @row;
		if( $type =~ /(date)|(time)/i ) { $time_type = $type; }
		}

	$type_sth->finish();

	return $time_type;
	}

# This subroutine gets the values from the database.table specified.
#
sub get_diffs
	{
	my $dbh		= shift;
	my $db		= shift;
	my $table	= shift;
	my $idx_col	= shift;
	my $idx		= shift;
	my @columns	= @_;

	my %values;

	my $select = join(",", @columns);
	my $get_sth = $dbh->prepare
		(
	 		qq{
				SELECT $select
				FROM $db.$table
				WHERE $db.$table.$idx_col = "$idx"
				}
		);

	$get_sth->execute();
	while( my @row = $get_sth->fetchrow_array() )
		{
		foreach my $col (@columns)
			{
			$values{$col} = shift( @row );
			}
		}
	$get_sth->finish();

	return \%values;
	}


# This subroutine takes the information and creates a handy formatted
# way of displaying the differences.
sub diff_message
	{
	my $from_idx			= shift;
	my $to_idx				= shift;
	my $idx					= shift;
	my $from_values_ref		= shift;
	my $to_values_ref		= shift;
	my $column_equiv_ref	= shift;
	my @diff_cols			= @_;

	my $message;

	my ($rule, $from_rule, $to_rule, $from_titles, $from_data, $to_data, $to_titles);

	my (@hr_arr, @f_titles, @f_data, @t_data, @t_titles);


	my ($intro_rule, $from_table_title, $table_title_rule, $to_table_title) =
	string_equalize( " ", "$from_db.$from_table", " ", "$to_db.$to_table" );

	my ($hr, $ft, $fd, $td, $tt) =
	string_equalize( " ", $from_idx, $idx, $idx, $to_idx );
	push @hr_arr,	$hr;
	push @f_titles,	$ft;
	push @f_data,	$fd;
	push @t_data,	$td;
	push @t_titles,	$tt;

	foreach my $col (@diff_cols)
		{
		my @output_columns =
		string_equalize (
						" ",
						$$column_equiv_ref{$col},
						$$from_values_ref{ $$column_equiv_ref{$col} },
						$$to_values_ref{$col},
						$col
						);

		( $hr, $ft, $fd, $td, $tt ) = @output_columns;
		push @hr_arr,	$hr;
		push @f_titles,	$ft;
		push @f_data,	$fd;
		push @t_data,	$td;
		push @t_titles,	$tt;
		}

	$table_title_rule =~ s/ /_/gi;

	$rule			= join( " | ", $intro_rule, @hr_arr	);
	$from_titles	= join( " | ", $intro_rule, @f_titles);
	$from_data		= join( " | ", $table_title_rule, @f_data	);
	$to_data		= join( " | ", $intro_rule, @t_data	);
	$to_titles		= join( " | ", $intro_rule, @t_titles);

	$from_data =~ s/_ \|/__\|/gi;

	my $short_rule	= join( " | ", @hr_arr	);

	$rule =~ s/ /-/gi;
	$rule =~ s/\|/+/gi;
	$short_rule =~ s/ /-/gi;
	$short_rule =~ s/\|/+/gi;

	$from_rule	= "$from_table_title +-$short_rule";
	$to_rule	= "$to_table_title +-$short_rule";


	$message = "+-$rule-+\n| $from_titles |\n| $from_rule-+\n|_$from_data |\n| $to_data |\n| $to_rule-+\n| $to_titles |\n+-$rule-+";

	return $message;
	}


# This subroutine takes a list of strings, and makes
# all the strings the same length by padding them out.
sub string_equalize
	{
	my @strings = @_;

	my $max_length = 0;
	foreach my $string (@strings)
		{
		my $length = length( $string );
		if ($length > $max_length
		) { $max_length = $length; }
		}

	foreach my $string (@strings)
		{
		$string = sprintf( "%${max_length}s", $string );
		}

	return( @strings );
	}


=pod

=head1 AUTHOR

Ross A. Beyer, rbeyer@lpl.arizona.edu

=head1 SEE ALSO

mysql

=cut
