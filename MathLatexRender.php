<?php

/**
 * Takes LaTeX fragments, sends them to a latex distro for rendering
 *
 * @author Julian Diaz
 * Based on the MathTexvc.php devloped by several contributors, and on the work of 
 * Jesse B. Dooley for the MathLatex Extension publisehd at http://mathlatex.sourceforge.net 
 *
 * In order to use this render, the following settings are required in LocalSettings.php
 *
 * wfLoadExtension( 'Math' );
 * $wgUseTeX= true;
 * $wgLatexOnWindows = true;
 * $wgPdfLaTexCommand = "D:\\texlive\bin\win32\pdflatex.exe";
 * $wgDvipngCommand = "D:\\texlive\bin\win32\dvipng.exe";
 */


class MathLatexRender extends MathTexvc {
    private $equation = '';


// Overides
    public function render() {
        global $wgMathDirectory;
        // test for '%' as the first character in $equation
        // If true pass the equation, with no wrapper, to LaTeX.
        // If fail, format the equation and pass it to LaTeX.
        if( ($this->tex[0] == '%' ) == false ) {
            $this->equation = $this->wrapper( $this->tex );
        }

        $tmpDir = wfTempDir();
        $this->setHash(md5(rand()));
        $tempFsFile = new TempFSFile( "$tmpDir/{$this->getHash()}.png" );
        //$tempFsFile->autocollect(); // destroy file when $tempFsFile leaves scope

        // call LaTeXrender
        // returns true on success or error string on failure
        $render_return = $this->LaTeXrender();

        // test for error string
        if( is_string( $render_return ) == true ){
            $msg = "<span tyle=\"color:red\">Render::render</span> failed<br />\n" .
            $render_return . "<br />\n";
            wfDebugLog( 'MathLatexRender', $msg );
            return $msg;
        }

        // call DviPNGrender
        // returns true on success or error string on failure
        $render_return = $this->DviPNGrender();

        // test for error string
        if( is_string( $render_return ) == true ){
            $msg = "<span style=\"color:red\">Render:DviPNGrender</span> failed<br />\n" .
            $render_return . "<br />\n";
            wfDebugLog( 'MathLatexRender', $msg );
            return $msg;
        } elseif ( !file_exists( "$tmpDir/{$this->getHash()}.png" ) ) {
            return $this->getError( 'math_image_error' );
        } elseif ( filesize( "$tmpDir/{$this->getHash()}.png" ) == 0 ) {
            return $this->getError( 'math_image_error' );
        }

        $hashpath = $this->getHashPath(); // final storage directory
        $backend = $this->getBackend();
        # Create any containers/directories as needed...
        if ( !$backend->prepare( [ 'dir' => $hashpath ] )->isOK() ) {
            return $this->getError( 'math_output_error' );
        }
        // Store the file at the final storage path...
        // Bug 56769: buffer the writes and do them at the end.
        if ( !isset( $wgHooks['ParserAfterParse']['FlushMathBackend'] ) ) {
            $backend->mathBufferedWrites = [];
            $wgHooks['ParserAfterParse']['FlushMathBackend'] = function () use ( $backend ) {
                global $wgHooks;
                unset( $wgHooks['ParserAfterParse']['FlushMathBackend'] );
                $backend->doQuickOperations( $backend->mathBufferedWrites );
                unset( $backend->mathBufferedWrites );
            };
        }
        $backend->mathBufferedWrites[] = [
            'op'  => 'store',
            'src' => "$tmpDir/{$this->getHash()}.png",
            'dst' => "$hashpath/{$this->getHash()}.png",
            'ref' => $tempFsFile // keep file alive
        ];

        $status_code = copy("$tmpDir/{$this->getHash()}.png","$wgMathDirectory/{$this->getHashSubPath()}/{$this->getHash()}.png");

        // $this->cleanTemporaryDirectory();

        if (!$status_code) { 
                $this->_errorcode = 6; 
                return false; 
        }

        // Get here and everything worked.
        return true;
    }/**
 * private functions
 */

/**
 * LaTeXrender
 * 
 * @brief Convert the latex statement to a dvi
 *
 * @function
 * @name LaTeXrender
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * Modified by Julian diaz on December 1, 2016. From the Original Versio 1.0
 *
 */
     private function LaTeXrender( ) {
        global $wgPdfLaTexCommand;

        $tmpDir = wfTempDir();
        $tempFsFileName = $this->getHash();

        wfDebugLog( 'MathLatexRender', $tmpDir );

        if( chdir ( $tmpDir ) == false ) {
            $msg = "<span style=\"color:red\">Render:LaTeXrender chdir</span> failed<br />\n";
            wfDebugLog( 'MathLatexRender', $msg );
            return $msg;
        }

        // write out the tex file in $MathTempPath
        $file_handle = fopen( $tempFsFileName . ".tex", "w");

        // test for a valid filehandle
        if( $file_handle == false ) {
            $msg = "<span style=\"color:red\">Render:LaTeXrender fopen</span> failed<br />\n";
            wfDebugLog( 'MathLatexRender', $msg );
            return $msg;
        }

        // write out the equation file
        if( fwrite($file_handle, $this->equation ) == false) {
            $msg = "<span style=\"color:red\">Render:LaTeXrender fwrite</span> failed<br />\n";
            wfDebugLog( 'MathLatexRender', $msg );
            return $msg;
        }

        // fwrite succeeded, close
        if( fclose( $file_handle ) == false ) {
            $msg = "<span style=\"color:red\">Render:LaTeXrender fclose</span> failed<br />\n";
            wfDebugLog( 'MathLatexRender', $msg );
            return $msg;
        }

        // have the input file.
        // assemble the latex call
        $cmd = $wgPdfLaTexCommand.
               ' --fmt=latex ' .               // use latex format
               '--interaction=nonstopmode ' . // don't stop, no point in it
               $tempFsFileName . ".tex";        // source file

        $retval = null;
        $contents = wfShellExec( $cmd, $retval );

        // verify if tex was produced.
        if ( file_exists( $tempFsFileName . ".tex" ) == false ) {
            $msg = "<span style=\"color:red\">Render:LaTeXrender tex creation</span> failed<br />\n" .
            "cmd " . $cmd . "<br />\n".
            "retval " . $retval . "<br />\n" .
            "result " . $contents . "<br />\n";
            wfDebugLog( 'MathLaTeX', $msg );
            return $msg;
        } else {
            return true;
        }
    } // LaTeXrender

/**
 * DviPNGrender
 *
 * @brief Convert a dvi file to the $MathImageExt image
 *
 * @function
 * @name DviPNGrender
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * Modified by Julian diaz on December 1, 2016. From the Original Versio 1.0
 */
     private function DviPNGrender() {
        global $MathDotsPerInch;
        global $wgDvipngCommand;

        $tmpDir = wfTempDir();
        $tempFsFileName = $this->getHash();

        // change current dir to $MathTempPath
        if( chdir ( $tmpDir ) == false ) {
            $msg = "<span style=\"color:red\">Render:DviPNGrender chdir</span> failed<br />\n";
            wfDebugLog( 'MathLaTeX', $msg );
            return $msg;
        }

        // assemble the dvipng call
        $cmd = $wgDvipngCommand .          // dvipng.exe command
               ' -bg Transparent ' .     // set background to transparent
               '--gamma 1.5 ' .         // color interpolation
               '-D 120' . ' ' . // output resolution
               '-T tight ' .            // reduce image size to just the equation
               '--strict ' .            //  don't stop, no point in it
               $tempFsFileName . '.dvi ' . // input file
               '-o ' .
               $tempFsFileName . ".png";  // output file

        $retval = null;
        $contents = wfShellExec( $cmd, $retval );

        // verify if png was produced.
        if( file_exists( $tempFsFileName . ".png" ) == false ) {
            $msg = "<span style=\"color:red\">Render::DviPNGrender png creation</span> failed<br />\n" .
            "cmd " . $cmd . "<br />\n" .
            "retval " . $retval . "<br />\n" .
            "dvipng result " . $contents . "<br />\n";
            wfDebugLog( 'MathLaTeX', $msg );
            return $msg;
        } else {
            return true;
        }
    } // DviPNGrender


/**
 * wrapper
 *
 * @brief Wrap the latex statement in commands for texlive
 *
 * @function
 * @name onParserFirstCallInit
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @param string latex statement
 * @return string latex statement wrapped
 */
     public static function wrapper( $plain_text ) {
        return  "\\nonstopmode" .
                "\n" .
                "\\documentclass[12pt]{article}" .
                "\n" .
                "\\usepackage{mathtools}" . // texlive-collection-latexrecommended
                "\n" .
                "\\usepackage{lmodern}" .
                "\n" .
                "\usepackage{amsmath}" .  // texlive-collection-latex
                "\n" .
                "\\usepackage{amsfonts}" .
                "\n" .
                "\\usepackage{amssymb}" .
                "\n" .
                "\\usepackage{pst-plot}" . // texlive-collection-pstricks
                "\n" .
                "\\usepackage{color}" .
                "\n" .
                "\\pagestyle{empty}" .
                "\n".
                "\\begin{document}" .
                "\n" .
                "$$" .
                "\n" .
                $plain_text .
                "\n" .
                "$$" .
                "\n" .
                "\\end{document}\n";
    } // wrapper



    /**
     *  Remove Temporary files
     * 
     *  Original work from Benjamin Zeiss 
     *
     * Modified by Julian diaz on December 1, 2016.
     */
    function cleanTemporaryDirectory() {
        

        $tmpDir = wfTempDir();
        $tempFsFileName = $this->getHash();

        // change current dir to $MathTempPath
        if( chdir ( $tmpDir ) == false ) {
            $msg = "<span style=\"color:red\">Render:DviPNGrender chdir</span> failed<br />\n";
            wfDebugLog( 'MathLaTeX', $msg );
            return $msg;
        }

        unlink($tempFsFileName.".tex");
        unlink($tempFsFileName.".aux");
        unlink($tempFsFileName.".log");
        unlink($tempFsFileName.".dvi");
        unlink($tempFsFileName.".png");
    }
}