## LINK directive

Syntax:
```
LINK="filename", "field1", "field2", …"fieldN"
```
This directive is used to link data, written as a text file (CSV format) or a spreadsheet (with xls, xlsx, or ods extensions), with the current script. The data linked are referenced in the script as sequences. If the fields' names are omitted, the fields are referenced using the names contained in the first row of the file.

For text files, the caracter used to separate field's data can be changed using the LINKSEP directive. See also the LINKMULTI directive if you need to duplicate the data rows, and the "Linked data editor" chapter. If you omit the "filename" parameter, the program reads the data directly from the script file, until it reads a ENDLINK directive.

For spreadsheet files, you can specify the sheet name after the filename, separated by a "!" character (if you omit the sheet name, the program reads the first sheet).

A sequence of file names can also be specified in the 1st parameter. If you omit the "filename" parameter, the program reads the data directly from the script file, until it reads a ENDLINK directive.

Note: accented or foreign characters are translated into HTML codes, to be used with HTMLTEXT. If you want to disable this feature, add a LINKUNI=OFF line before the LINK line.

Note: if you indicate the same field names on multiple LINK lines, empty elements will be added to the sequences if those fields are not present in a sheet.

Tip: you can choose a name (and path) from a Windows standard dialog, clicking on the button "Insert" and choosing the menu voice "Link".

Tip: if you drag and drop a spreadsheet file in nanDECK's window, a LINK line is added with the path and filename of the spreadsheet.

Examples:

LINK=c:\test\data01.txt

LINK=c:\test\data02.txt, size, speed, weight

In the script these fields are referenced as [size], [speed] and [weight].

You can also link a Google Sheet document, using the ID of the file instead of "filename" parameter, but you must share it first, following these steps:

• select the file in Google Drive web page,
• click the Share icon (the icon with the "little man" in top-right button bar),
• click the dropdown menu below "Link sharing on" in the window,
• select a link sharing option, one of the "Anyone with the link…" option.

Now Google shows you a link like this:
https://docs.google.com/spreadsheets/d/1s_p1gcL2BBO_zYIe_v8bADjWzFtc0hh_eY8DIw8OPfY/edit?usp=sharing

The ID of the sheet is the bold part, copy it and paste it in a nanDECK line like this:

LINK=1s_p1gcL2BBO_zYIe_v8bADjWzFtc0hh_eY8DIw8OPfY

You can also select one of the sheets, with this syntax:

LINK=ID!Sheet_name

Example:

LINK=1s_p1gcL2BBO_zYIe_v8bADjWzFtc0hh_eY8DIw8OPfY!Beta

But you must enable the web sharing, with these steps:
• open the spreadsheet in a browser,
• select from menu File -> Publish to the Web,
• click on "Publish" button.

## TEXT directive

Syntax:
```
TEXT="range"Range, "text", pos x, pos y, width, height, horizontal alignment, vertical alignment, angle, alpha, outline width, circle offset, circle angle, width factor, height factor
```
This directive prints the text "text" in the cards specified by "range".

Parameters:

"range": a set of cards

"text": the text to be printed

pos x: horizontal position (in cm)

pos y: vertical position (in cm)

width: width of the text's rectangle (in cm)

height: height of the text's rectangle (in cm)

horizontal alignment: the text's horizontal alignment in the rectangle, values accepted are:

left: left aligned
center: centered
right: right aligned

This parameter is optional, if omitted it is considered to be equal to "center"

vertical alignment: the text's vertical alignment in the rectangle, values accepted are:

top: top aligned
center: centered
bottom: bottom aligned
wordwrap or wwtop: the text is top aligned and word-wrapped in the rectangle
wwcenter: the text is center aligned and word-wrapped in the rectangle
wwbottom: the text is bottom aligned and word-wrapped in the rectangle
charwrap: the text is centered, spaced and word-wrapped (every character) in a pattern

This parameter is optional, if omitted is considered to be equal to "center"

angle: angle of text rotation, if omitted is considered to be 0 (no rotation)

alpha: level of transparency of text, from 0 (full transparent) to 100 (full solid). If omitted, the level is set to 100 (full solid)

outline width: if you specify a number, the font is drawn as outlined, with this number as line's width

circle offset: if you specify a number, this is the offset for a circular text (from 0 to 100), the default is 25

circle angle: if you specify a number, this is the angle of each letter in a circular text, the default is 0

width factor: if you specify a number, the text is horizontally stretched (the default is 100)

height factor: if you specify a number, the text is vertically stretched (the default is 100)

Examples:

TEXT="1-3", "Werewolf", 0, 0, 6, 2, center

TEXT="4", "Seer", 0, 0, 6, 2, center, center, 90

TEXT="1", "Nel mezzo del cammin di nostra vita", 1, 1, 5, 7, center, wordwrap

## HTMLFONT directive

Syntax:
```
HTMLFONT = tag, "font name", font size, style, html colorHTML color, alignment, shadow x, shadow y, shadow blur, shadow color, outline color, outline width, indent, highlight color, char. spacing, angle, CSS
```
This directive creates a tag that can be used for recalling a font in an HTMLTEXT directive, for example, if the tag is name, the string "one <name>two</name> three" has the word "two" written with the font defined by it; tags can be nested, therefore if you want the other words to be written with other font, you can use the string "<other>one <name>two</name> three</other>". If you create a tag with name example, in HTMLTEXT you can also assign these characteristics in an HTML text using example as the the 11th parameter. If an alignment is specified, the program creates a "paragraph" font (a <div> tag in HTML) therefore its text is organized in a new paragraph (with a newline added when it's closed), otherwise the program creates a "text" font (a <span> tag in HTML). In other words, if you want to use different font on the same line, it's possible only by omitting the alignment parameter. Note that for default the text in HTMLTEXT is word-wrapped, so there isn't a flag to enable it. There are three special tags: th, tr, and td, that are used with the HTML tags of the same names (i.e. in tables).

There are three special tags: th, tr, and td, that are used with the HTML tags of the same names (i.e. in tables).

Parameters:

tag: a name that is used for recalling the font in a HTMLTEXT directive.Gradient

"font name": character's name (string)

font size: character's size (integer), in typographical points = 1/72 of an inch

style: character' style, values accepted are:

B	bold
I	italic
U	underline
S	strikeout
O	shadow over outline (the default is outline over shadow)
N	do not resize this font when using F flag in HTMLTEXT
P	resize this font proportionally to the main font when using F flag in HTMLTEXT
R	the outline of the font is done in a more refined way
C	break lines at every character
T	the HTML syntax is formatted for table cells
A	small caps
M	multiple shadows (use sequences for parameters from 7th to 10th)
D	keep decimals in the font size calculation
L	round down to integers the font size calculation
G	last line aligned to the right
E	last line aligned to the center
J	last line justified
H	automatic hyphenation (you must specify the language with an HTMLLANG directive)
Y	manual hyphenation (you must specify the syllables using one or more &shy; tag).
Z	forces the text on a single line
W	the text is converted to upper case
V	the text is converted to lower case
F	the first letter of the text is converted to upper case, the others to lower
X	to add the HTML tag for overflow: auto
1	to add the HTML tags for a line of an unordered list
2	to add the HTML tags for a line of an ordered list

html color: character's color, in the same format used for HTML

alignment: the text's horizontal alignment, values accepted are:

left	left aligned
center	centered
right	right aligned
justify	the text is justified

The horizontal alignment is optional, if omitted is considered to be equal to left
Gradient
shadow x: the horizontal offset for a shadow drawn under the text. Note: all the shadow's parameters work only with flag E, and Internet Explorer must be version 11 or more

shadow y: the vertical offset for a shadow drawn under the text

shadow blur: if you specify this parameter, the shadow is blurred

shadow color: the color for the text' shadow, in the same format used for HTML

outline color: the color for the text's outline, in the same format used for HTML

outline width: the width for the text's outline

indent: the indentation of the first line (you can specify a negative number for hanging indentation)

highlight color: color for the background of the text, in the same format used for HTML

char. spacing: the spacing used between each character, it can be negative

angle: the angle of the rotation for the characters

CSS: this text is added to the image definition as a CSS HTML style

Example:
```
HTMLFONT = alfa, Arial, 32, , #000000
HTMLFONT = beta, "Times New Roman", 18, I, #0000FF
HTMLTEXT = 1, "<beta>This is a </beta><alfa>test</alfa>", 0, 0, 100%, 100%
```
## HTMLTEXT directive

Syntax:
```
HTMLTEXT="range"Range, "text", pos x, pos y, width, height, html colorHTML color, angle, flags, alpha, htmlfont1, htmlfont2, separator, width factor, height factor
```
This directive prints a text, using HTML format, in the cards specified by "range". This directive is useful if you want to print a text with multiple size, font, attributes, colors and so on. For expressions, you must include them in double curly parentheses {{ … }} (or change the symbol in an EXPRESSION line). You can add also one or more images, using a keyword(s), coded with the HTMLIMAGE directive.

Parameters:

"range": a set of cards

"text": the HTML text to be printed

pos x: horizontal position (in cm)

pos y: vertical position (in cm)

width: width of the text's rectangle (in cm)

height: height of the text's rectangle (in cm)

html color: background color for text

angle: angle of text rotation, you must specify 0 for no rotation

flags: you can specify one or more flags, chosen between:

T	Transparent background for text
H	Horizontal mirror
V	Vertical mirror
I	HTML rendering with old internal engine (default)
A	HTML rendering with new internal engine
E	HTML rendering with MS Explorer
K	HTML rendering with Google Chrome
2	Render a x2 image (better use the OVERSAMPLE directive)
4	Render a x4 image (better use the OVERSAMPLE directive)
8	Render a x8 image (better use the OVERSAMPLE directive)
R	Vertical text
B	Transparent background, better rendering of png, does not work with I flag
F	The text is reduced to fit the rectangle, does not work with I flag
S	The text is reduced to fit the rectangle and the size is saved for the next cards, does not work with I flag
J	The text is enlarged to fit the rectangle, does not work with I or F flag (in the latter case, F takes precedence)
G	The width is reduced to set the lines with the same length
M	The images are resized with the text, works only with F flag
L	TEXTLIMIT variables are calculated more accurately, does not work with I flag
O	Replace tags also between < and >
U	The fonts in 12th parameter are applied sequentially
C	Clear page after rendering, works only with E flag
N	Use always a new instance, works only with E flag
Y	Keep the HTML file on disk, for debugging, does not work with internal engines
Z	Instead of a temporary file on disk, use RAM, works only with E flag
X	Instead of a temporary file on disk, use RAM (alternate method), works only with E flag
Q	Instead of a temporary file on disk, use RAM (alternate method), works only with E flag

alpha: level of transparency of text, from 0 (full transparent) to 100 (full solid). If omitted, the level is set to 100 (full solid)

htmlfont1: add to the whole text the start and end tags for a font defined by an HTMLFONT directive

htmlfont2: add to each paragraph the start and end tags for a font defined by an HTMLFONT directive

separator: you can specify the separator of each paragraph (if not specified, it is <br>)

width factor: if you specify a number, the text is horizontally stretched (the default is 100)

height factor: if you specify a number, the text is vertically stretched (the default is 100)

Note: to add a line break in the text you can use the <br> characters, or put a line break in the spreadsheet with Alt+return and add LINKNEW=<br> before the LINK line.

Example:
```
HTMLTEXT=1, "<p><b>bold</b>, <i>italic</i>, <u>underline</u></p>", 0, 0, 6, 6, #FFFF80, 0
```
## IMAGE directive

Syntax:
```
IMAGE = "range"Range, "image file", pos x, pos y, width, height, angle, flag, alpha, texture width, texture height, skew x, skew y, img width, img height, loc x, loc y, copy x, copy y
```
This directive draws an image file in a set of cards.

Parameters:

"range": a set of cards

"image file": an existent image file (optionally with a path), formats allowed are .bmp, .gif, .png and .jpg

pos x: horizontal position (in cm)

pos y: vertical position (in cm)

width: width of the image (in cm)

height: height of the image (in cm)

angle: angle of image rotation, can be 0 for no rotation.

"flag": in this parameter you can specify any, some or all these letters:

T	Transparent
A	Antialiasing
P	Proportional
G	Grayscale
X	Texture
H	Horizontal mirror
V	Vertical mirror
N	Use PNG transparency
R	Don't adjust size for rotated images
D	Use DPI from image file (BMP, PNG, and JPG)
C	Extends the image cropping the borders
I	Reads the EXIF tags for orientation from file
J	Use an alternate library for loading images in jpeg format
U	Align the image to the upper boundary of the rectangle (with P/C flag)
E	Align the image to the right boundary of the rectangle (with P/C flag)
S	Align the image to the lower boundary of the rectangle (with P/C flag)
W	Align the image to the left boundary of the rectangle (with P/C flag)
O	Crop the source image (x, y, width, and height specified in parameters from 10th to 13th)

If the "T" flag is used, the transparent color is assumed to be the first pixel's (top left pixel).

alpha: level of transparency of image, from 0 (full transparent) to 100 (full solid). If omitted, the level is set to 100 (full solid)

texture width: width of the texture (in cm), used only with "X" flag, if omitted the default is the image file's width

texture height: height of the texture (in cm), used only with "X" flag, if omitted the default is the image file's height

skew x: draw the image shifted horizontally (to the right for positive number, to the left for negative), the value 1 is the image's width (you can use a decimal value)

skew y: draw the image shifted vertically (to the bottom for positive number, to the top for negative), the value 1 is the image's height (you can use a decimal value)

img width: if this parameter is specified, the image isn't enlarged to the whole rectangle, but instead is drawn with this width (in cm)

img height: if this parameter is specified, the image isn't enlarged to the whole rectangle, but instead is drawn with this height (in cm)

loc x: if this parameter is a positive value, the image is positioned at that % of width, with a width equal to the parameter image width; if this parameter is a negative one, the image is split horizontally at that % of his width, and the two halves are positioned at the edge of the rectangle

loc y: if this parameter is a positive value, the image is positioned at that % of height, with a height equal to the parameter image height; if this parameter is a negative one, the image is split vertically at that % of his height, and the two halves are positioned at the edge of the rectangle

copy width: if the image is split horizontally, the empty gap between the two halves is filled with a % of the image, starting from the cut point

copy height: if the image is split vertically, the empty gap between the two halves is filled with a % of the image, starting from the cut point

Examples:
```
IMAGE="1-10", "house.bmp", 0, 1, 3, 3, 0

IMAGE="7-14,20", "c:\images\dragon.gif", 5, 2, 1, 2, 180,"T", 50
```
## ICON directive

Syntax:
```
ICON = "range"Range, key, "image file", width factor, height factor, horizontal offset, vertical offset, alpha, angle
```
This directive assigns one or more characters (a "key") to an image, to be used later with an ICONSICONS directive directive. Note that if the key is longer than one character (the default), it must be specified as 14th parameter in ICONS.

Parameters:

"range": a set of cards

key: one or more characters used to identify the image (like "A" or "001")

"image file": an existent image file (optionally with a path), formats allowed are .bmp, .gif and .jpg

width factor: the width of the image is adjusted with this factor, a value larger than 100 enlarges the width of the image, a value less than 100 shrinks the width of the image (if not specified, the width factor of the image is 100)

height factor: the height of the image is adjusted with this factor, a value larger than 100 enlarges the height of the image, a value less than 100 shrinks the height of the image (if not specified, the height factor of the image is 100)

horizontal offset: the horizontal position of the image is adjusted with this factor, 100 is the width of the image (if not specified, the offset is zero)

vertical offset: the vertical position of the image is adjusted with this factor, 100 is the height of the image (if not specified, the offset is zero)

alpha: level of transparency of image, from 0 (full transparent) to 100 (full solid). If omitted, the level is read from the ICONS line

angle: the rotation angle of the icon (the default is zero)

Examples:
```
ICON="1-10", A, image1.jpg
ICON="1-10", B, image2.jpg
ICON="1-10", C, image3.jpg
```
## ICONS directive

Syntax:
```
ICONS = "range"Range, keys, pos x, pos y, width, height, obj width, obj height, angle, flag, horizontal alignment, vertical alignment, alpha, key length, width factor, height factor, frames
```
This directive prints a defined number of images in a rectangular area (like a multi-image PATTERNPATTERN directive directive), the "keys" parameter identifies the images used, defined before with ICONICON directive directivee, note that by default each key is one character long, but you can have a longer key by specifying the length in the 14th parameter. For example, if you write:
```
ICON="1-10", A, image1.jpg
ICON="1-10", B, image2.jpg
ICON="1-10", C, image3.jpg
```
You can use a key of "ABC" in an ICONS line to print the three images all together in a rectangular area. This directive is useful when you must convert to images an output from the combination/permutation engine. You can also use these special characters:

`<` adds a backspace and draw two images in the same place; for example, a key like "P<2" means that the image assigned to "2" is printed over the image assigned to "P".

`>` adds a newline; for example, a key like "A>BB" draws first the image "A", and two images "B" in a new line, i.e. the three images are arranged in a triangle pattern.

_ the image corresponding to the next key is shown in the current icon space, merged with the next. For example _A means that image A is shown in the merged space of two icons; __A means that image A is shown in the merged space of three icons.

Note: if you have a key longer than one character, these special characters must be replicated as well (i.e. with a key length of two, the special characters become "<<", ">>", "__".

Parameters:

"range": a set of cards

keys: a string, composed of characters assigned to images with ICONICON directive directives

pos x: horizontal position (in cm)

pos y: vertical position (in cm)

width: width of the rectangle space (in cm)

height: height of the rectangle space (in cm)

obj width: width of the single object to be printed (in cm)

obj height: height of the single object to be printed (in cm)

angle: angle of image rotation, if not specified it is assumed to be 0 (for no rotation).

flag: in this parameter you can specify a special behavior for images, possible values are:

T	Transparent
A	Antialiasing
R	Reverse, reversing the filling order of pattern's elements (from bottom to top)
N	Use PNG transparency
P	Proportional
C	Extends the image cropping the borders
V	Vertical pattern
W	Distribute icons in width
H	Distribute icons in height
L	Keeps icons on a single line
X	Use variable length for the key (the length parameter is not used)

horizontal alignment: the images' horizontal alignment in the rectangle, values accepted are:

left: left aligned
center: centered (the default)
right: right aligned

vertical alignment: the images' vertical alignment in the rectangle, values accepted are:

top: top aligned
center: centered (the default)
bottom: bottom aligned

alpha: level of transparency of image, from 0 (full transparent) to 100 (full solid). If omitted, the level is set to 100 (full solid)

key length: the default lenght of the character string utilised for key is one character, but a different lenght can be specified here, the "keys" parameter length must be a multiple

width factor: the width of the space for an image is adjusted with this factor, a value larger than 100 enlarges the width of the space, a value less than 100 shrinks the width of the space (if not specified, the width factor of the space is 100)

height factor: the height of the space for an image is adjusted with this factor, a value larger than 100 enlarges the height of the space, a value less than 100 shrinks the height of the space (if not specified, the height factor of the space is 100)

frames: a list of frames (optional) in which the icons are drawn (one icon for each frame)

Examples:
```
PATTERN="1-10", "ABC", 0, 1, 6, 3, 2, 2, 0, , left, center

PATTERN="1-10", "A1B2C3", 0, 1, 6, 3, 2, 2, 0, , left, center, 100, 2
```

## CARDS directive

Syntax:
```
CARDS=number
```
This directive sets the number of cards of the project.

Example:
```
CARDS=54
```
## BORDER directive

Syntax:
```
BORDER=type, html colorHTML color, thickness, guidelines, guide color, mark/cross/line size, hor. guide offset, ver. guide offset, num. of guides, hor. gap offset, ver. gap offset, thickness
```
This directive draws a border around all cards.

Parameters:

type: the type of border can be chosen between:

RECTANGLE	draws a rectangle (the default)
ROUNDED	draws a rectangle with rounded corners
MARK		draws cut marks (the length is set with the 6th parameter)
NONE		no border

html color: color of the border, in the same format used for HTML, black if not specified

Note: if you want a different border color on each card, use instead RECTANGLE or ROUNDRECT, with the internal color as EMPTY.

thickness: thickness of the border (in cm), if omitted, it is 1 pixel wide

Note: the thickness of the border is measured on two cards; if you use a thickness of 1 cm, for example, on each card the border is 0.5 cm wide. 

guidelines: this is for drawing lines beyond the card's boundaries (over the page's margins); you can choose between:

NONE	no guidelines (the default)
SOLID	solid lines
DOTTED	dotted lines
DASHED	dashed lines
MARK	draws cut marks only (solid lines)
MARKDOT	draws cut marks only (dotted lines)
MARKDASH	draws cut marks only (dashed lines)
CROSS	draws cut marks and crosses (solid lines, the length is set with the 6th parameter)
CROSSDOT	draws cut marks and crosses (dotted lines, the length is set with the 6th parameter)
CROSSDASH	draws cut marks and crosses (dashed lines, the length is set with the 6th parameter)
LINE	draws cut marks and lines (solid lines, the length is set with the 6th parameter)
LINEDOT	draws cut marks and lines (dotted lines, the length is set with the 6th parameter)
LINEDASH	draws cut marks and lines (dashed lines, the length is set with the 6th parameter)

guide color: color of the guidelines, in the same format used for HTML, black if not specified; If multiple guides are specified in the 9th parameter, multiple colors can be indicated here

mark/cross/line size: length of the cut marks (in cm) for MARK border type, and length of the arm of the cross (in cm) for CROSS/CROSSDOT/CROSSDASH guideline type, length of the line (in cm) outside the card for LINE/LINEDOT/LINEDASH guideline type

hor. guide offset: horizontal guides are displaced of an offset (in cm), zero if not specified

ver. guide offset: vertical guides are displaced of an offset (in cm), equal to horizontal offset if not specified

num. of guides: number of guides drawn (0 means a single line)

hor. gap offset: a gap in horizontal marks (in cm),  if zero (or not specified) the mark start at the cards,

ver. gap offset: a gap in vertical marks (in cm),  if zero (or not specified) the mark start at the cards.

thickness: thickness of the guideline (in cm), if omitted, it is 1 pixel wide

Note: the result of the BORDER directive is not displayed when the card is saved with SAVE, in this case it is better to use a RECTANGLE or ROUNRECT directive.

Examples:
```
BORDER=rectangle

BORDER=rounded, #0000FF, 0.5
```

## UNIT directive

Syntax:
```
UNIT=type
```
This directive chooses a unit to be used with all the numeric sizes in the script. For correct use, it is better to include it in the first line of the script. The default size unit, if UNIT is not used, is the "cm".

Parameters:

type: the type of unit can be chosen between:

CM

MM

INCH

Example:
```
UNIT=inch
```

## PAGE directive

Syntax:
```
PAGE=width, height, orientation, flags, html color, "no border range", edge color, edge left, edge right, edge top, edge bottom
```
This directive sets the size and orientation of the page sheet.

Parameters:

width: page width (in cm)

height: page height (in cm)

orientation: the orientation can be chosen between:

LANDSCAPE: horizontal

PORTRAIT: vertical

flags: in this parameter you can specify a special behavior for pages, possible values are:

H	the cards are horizontally centered
V	the cards are vertically centered
E	guides are not printed on even pages
O	guides are not printed on odd pages
M	the horizontal margins are mirrored in even pages
D	the page is centered (with H/V flags) without considering the edges
L	guides are not printed on the left half of the page
R	guides are not printed on the right half of the page
T	guides are not printed on the top half of the page
B	guides are not printed on the bottom half of the page
U	the page edges are drawn over the guidelines

html color: paper color, in the same format used for HTML; you can also use a sequence of two or more colors, if you want a different color on each page

"no border range": if a range is specified, the border isn't printed on these cards

edge color: edge color, in the same format used for HTML; you can also use a sequence of two or more colors, if you want a different color on each page

edge left: the horizontal width of a colored area at the left edge of the page

edge right: the horizontal width of a colored area at the right edge of the page

edge top: the vertical height of a colored area at the top edge of the page

edge bottom: the vertical height of a colored area at the bottom edge of the page

If the directive PAGE is not specified, the standard is 21 x 29.7 (A4), portrait.

Example:
```
PAGE=21,29.7,"LANDSCAPE"
```

## DPI directive

Syntax:
```
DPI=dpi number
```
This directive sets the resolution of cards (in Dot Per Inches). If omitted, is considered to be 300.
Warning: with a value too high, the time of rendering can be very long.

Example:
```
DPI=150
```

## CARDSIZE directive

Syntax:
```
CARDSIZE=width, height
```
This directive sets the size of cards (in cm). If omitted, is considered to be 6 cm x 9 cm.

Examples:
```
CARDSIZE=5,10

CARDSIZE=2.5,2.5
```
## GAP directive

Syntax:
```
GAP=horizontal gap, vertical gap, line switch, fill switch, guidelines, guide color, cross size
```
This directive sets spaces between cards in printed pages.

Parameters:

horizontal gap: horizontal spaces (in cm)

vertical gap: vertical spaces (in cm)

line switch: values accepted are:

ON	to enable a guideline in the middle of the gap
OFF	to disable it (the default)

fill switch: values accepted are:

ON	when using CROSS guidelines, they are prolonged across the middle of the gap
OFF	the middle of the gap isn't drawn (the default)

guidelines: the graphic type of the middle guideline (if present). You can choose between:

NONE		no guideline
SOLID		solid lines
DOTTED	dotted lines
DASHED	dashed lines
MARK		draws cut marks only (solid lines)
MARKDOT	draws cut marks only (dotted lines)
MARKDASH	draws cut marks only (dashed lines)
CROSS	draws cut marks and crosses (solid lines, the length is set with the 6th parameter)
CROSSDOT	draws cut marks and crosses (dotted lines, the length is set with the 6th parameter)
CROSSDASH	draws cut marks and crosses (dashed lines, the length is set with the 6th parameter)

guide color: color of the middle guideline (if present), in the same format used for HTML, same color of other guidelines if not specified

cross size: length of the arm of the cross (in cm) for CROSS/CROSSDOT/CROSSDASH guideline type

If the directive GAP is not specified, there is no gap between cards.

Example:
```
GAP=1,1
```
## BASERANGE directive

Syntax:
```
BASERANGE="range"Range, switch
```
For each card in a range an element is extracted from a sequence, and as a default the first element from the sequence is paired from the first card in the range. The only exception is when you have a LABELRANGE function: in this case the nth element from the sequence is paired with the nth card from the deck. With this directive you can change this behavior.

Parameters:

"range": a set of cards

switch: values accepted are:

ON: the nth element from the sequence is paired with the nth card from the deck
OFF: the nth element from the sequence is paired with the nth card from the range

## RECTANGLE directive

Syntax:
```
RECTANGLE = "range"Range, pos x, pos y, width, height, html colorHTML color, html colorHTML color, thickness
```

This directive draws a filled rectangle in a set of cards.

Parameters:

"range": a set of cards

pos x: horizontal position (in cm)

pos y: vertical position (in cm)

width: width of the rectangle (in cm)

height: height of the rectangle (in cm)

html color: border color of the rectangle, in the same format used for HTML. You can also specify a gradientGradient

html color: inner color of the rectangle, in the same format used for HTML, if not specified the inner color is the same of border color. You can also specify "EMPTY" for a hollow (and transparent) rectangle or a gradientGradient

thickness: thickness of the rectangle (in cm), if omitted, the rectangle's border is 1 pixel wide

Examples:
```
RECTANGLE="1-10", 2, 3, 4, 4, #00FF00

RECTANGLE="1,5,7", 0, 4, 6, 2, #FF00FF, EMPTY

RECTANGLE="1-10", 2, 3, 4, 4, #FF0000#0000FF@90
```

## BACKGROUND directive

Syntax:
```
BACKGROUND = "range", pos x, pos y, width, height, frames, flags, width, red, green, blue
```

This directive draws an image using gradients created from colors read from rectangular or circular areas (specified by frames). For this directive you can use frames defined with the standard syntax, or use this special syntax:

```
<frame>=x, y, width, height, special
```

With this special syntax you can specify other frames, each created with a specific color and at a distance in percentage from the main frame. The special parameter can be composed of one or more colors, defined with this syntax: #000000%NN where NN is the % distance from the original frame. If you add an @ symbol to the end of the special parameter the color changes will be made from a circular shape rather than a rectangular one.

The special parameter can be composed of one or more colors, defined with one of these syntaxes:
#000000 is a color,
#000000%A is a color where A is the % distance from the original frame,
#000000%A$B is a color where A is the % distance from the original frame and B is a factor used to calculate the weight of this color.
If you add a @ symbol to the end of the special parameter the color changes will be made from a circular shape rather than a rectangular one. If you add a ! symbol to the start of the special parameter the background is drawn also inside this shape.

Parameters:

"range": a set of cards

pos x: horizontal position (in cm)

pos y: vertical position (in cm)

width: width of the rectangle (in cm)

height: height of the rectangle (in cm)

frames: the name of the frame(s) used for reading the colors

flags: one or more of the following flags:

E	the distances are measured using an "Euclidean" formula (square root and powers), this is the default
M	the distances are measured using a "Manhattan" formula (absolutes differences)
S	the variation specified with red/green/blue parameters is applied when there are more than half distances between the frames that are greater than the parameter distance
I	the color variation specified with red/green/blue parameters is applied when there are less than half distances between the frames that are greater than the parameter distance
N	the color variation specified with red/green/blue parameters is applied when the number of distances between the frames that are greater than the parameter distance is an even number
O	the color variation specified with red/green/blue parameters is applied when the number of distances between the frames that are greater than the parameter distance is an even number

distance: the distance used with flags S, I, N, and O

red: the variation (positive or negative) of the red component used with flags S, I, N, and O

green: the variation (positive or negative) of the green component used with flags S, I, N, and O

blue: the variation (positive or negative) of the blue component used with flags S, I, N, and O

## INCLUDE directive

Syntax:

```
INCLUDE="filename"
```

This directive includes another script file in the current script.

Examples:
```
INCLUDE=c:\test\alpha.txt

INCLUDE=beta.txt
```


---

## Image Rendering Learnings

### Rendering pass order

nanDECK renders elements in **two fixed passes**, regardless of script order:

1. **IMAGE pass** — all `IMAGE`, `RECTANGLE`, `BORDER`, and other drawing primitives
2. **HTMLTEXT pass** — all `HTMLTEXT` and `TEXT` directives rendered on top

**Consequence:** any `HTMLTEXT` element with a visible background (even `background:#ffffff`) will cover `IMAGE` results that appear later in the script. Use `RECTANGLE` instead of `HTMLTEXT` for any background region that must sit *behind* an image.

---

### LINK column alignment

The `LINK` directive maps columns **positionally**. If the field name list is shorter than the CSV column count, later columns shift left and named tokens resolve to the wrong data.

- Always count CSV columns and match them exactly in the `LINK` line.
- Dummy names (e.g. `"IMAGEDESCRIPTION"`) can be included for columns you don't use in the template but must still account for.

---

### IMAGE flags

| Flag | Effect |
|------|--------|
| `P`  | Proportional scale — image fits inside the box without distortion |
| `N`  | Preserve PNG transparency (alpha channel) |
| `C`  | Crop-fill — image covers the entire box, cropping edges if needed |
| `A`  | Antialiasing — smooths edges on resize |
| `T`  | Transparent — treats the top-left pixel colour as transparent (use on `HTMLTEXT` to punch through a white canvas and reveal images below) |

Recommended combos:
- Icons (PNG with transparency): `"PN"`
- Art fills (crop to frame): `"CA"`
- `HTMLTEXT` over an image region: add `"T"` to prevent the white canvas from obscuring art underneath

---

### Backgrounds behind images

Replace `HTMLTEXT` background divs with `RECTANGLE` so they stay in the IMAGE pass:

```
; WRONG – HTMLTEXT renders after all IMAGE directives, covering artwork
HTMLTEXT="1-44","<div style='background:#eef2f8'></div>",0.35,1.7,5.6,3.35,...

; CORRECT – RECTANGLE renders in the IMAGE pass; art IMAGE placed after will appear on top
RECTANGLE="1-44",0.35,1.7,5.6,3.35,#eef2f8,#dbe3ee,0,,100,0.25
IMAGE="1-44","[ARTFILE]",0.5,1.85,5.3,3.05,0,"CA",100
```

---

### TTS per-card PNG export

Set `PAGE` equal to `CARDSIZE` so each card occupies its own page. Combined with `/CREATEPNG`, this produces one PNG file per card numbered sequentially (`card_1.png` … `card_N.png`).

```
CARDSIZE=6.3,8.8
PAGE=6.3,8.8,"PORTRAIT",,"#FFFFFF"
```

Use an identical design script (same `IMAGE`, `RECTANGLE`, `HTMLTEXT` directives) so TTS assets are pixel-identical to the printed PDF.
