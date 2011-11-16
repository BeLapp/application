<?xml version="1.0" encoding="utf-8"?>
<!--
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
 *
 * LICENCE
 * OPUS is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or any later version.
 * OPUS is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details. You should have received a copy of the GNU General Public License 
 * along with OPUS; if not, write to the Free Software Foundation, Inc., 51 
 * Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * @category    Application
 * @package     Module_Frontdoor
 * @author      Felix Ostrowski <ostrowski@hbz-nrw.de> 
 * @author      Simone Finkbeiner <simone.finkbeiner@ub.uni-stuttgart.de> 
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2009-2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */
-->

<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:php="http://php.net/xsl"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:xml="http://www.w3.org/XML/1998/namespace"
    exclude-result-prefixes="php">
   
    <xsl:output method="xml" omit-xml-declaration="yes" />
 
    <xsl:param name="baseUrlServer" />
    <xsl:param name="baseUrl" />
    <xsl:param name="layoutPath" />
    <xsl:param name="isMailPossible" />

    <xsl:key name="list" match="/Opus/Opus_Document/Subject[@Type='uncontrolled']" use="@Language"/>

    <xsl:template match="/">
        <div about="{/Opus/Opus_Document/TitleMain/@Value}">
            <xsl:apply-templates select="Opus/Opus_Document" />
        </div>
    </xsl:template>

    <!-- Suppress spilling values with no corresponding templates -->
    <xsl:template match="@*|node()" />

<!-- here you can change the order of the fields, just change the order of the apply-templates-rows
     if there is a choose-block for the field, you have to move the whole choose-block
     if you wish new fields, you have to add a new line xsl:apply-templates...
     and a special template for each new field below, too -->
    <xsl:template match="Opus_Document">
        <div id="titlemain-wrapper">
            <xsl:call-template name="Title" />
        </div>

        <div id="result-data">
            <div id ="author">
                <xsl:call-template name="Author" />
            </div>
            
            <div id="abstract">
                <xsl:call-template name="Abstract" />
            </div>
        </div>

        <div id="services" class="services-menu">
            <xsl:if test="normalize-space(File/@PathName) and File[@VisibleInFrontdoor='1']">
                <div id="download-fulltext" class="services">
                    <h3>
                        <xsl:call-template name="translateString">
                            <xsl:with-param name="string">frontdoor_download_options</xsl:with-param>
                        </xsl:call-template>
                    </h3>
                    <ul>
                        <xsl:apply-templates select="File[@VisibleInFrontdoor='1']">
                            <xsl:sort select="@Label"/>
                        </xsl:apply-templates>
                    </ul>
                </div>
            </xsl:if>

            <div id="export" class="services">
                <h3>
                    <xsl:call-template name="translateString">
                        <xsl:with-param name="string">frontdoor_export_options</xsl:with-param>
                    </xsl:call-template>
                </h3>
                <ul>
                    <xsl:call-template name="ExportFunctions" />
                </ul>
            </div>

            <div id="additional-services" class="services">
                <h3>
                    <xsl:call-template name="translateString">
                        <xsl:with-param name="string">frontdoor_additional_options</xsl:with-param>
                    </xsl:call-template>
                </h3>
                <ul>
                    <xsl:call-template name="AdditionalServices"/>
                    <xsl:call-template name="MailToAuthor"/>
                </ul>
            </div>
        </div>
      
        <table class="result-data frontdoordata">
            <caption>Metadaten</caption>
            <colgroup class="angaben">
                <col class="name"/>
            </colgroup>
            <xsl:apply-templates select="PersonAuthor" />
            <xsl:apply-templates select="IdentifierUrn" />
            <xsl:apply-templates select="IdentifierUrl" />
            <xsl:apply-templates select="IdentifierHandle" />
            <xsl:apply-templates select="IdentifierDoi" />
            <xsl:apply-templates select="IdentifierIsbn" />
            <xsl:apply-templates select="IdentifierIssn" />
            <xsl:apply-templates select="IdentifierArxiv" />
            <xsl:apply-templates select="IdentifierPubmed" />
            <xsl:apply-templates select="ReferenceUrn" />
            <xsl:apply-templates select="ReferenceUrl" />
            <xsl:apply-templates select="ReferenceDoi" />
            <xsl:apply-templates select="ReferenceHandle" />
            <xsl:apply-templates select="ReferenceIsbn" />
            <xsl:apply-templates select="ReferenceIssn" />
            <xsl:apply-templates select="TitleParent" />
            <xsl:apply-templates select="TitleSub" />
            <xsl:apply-templates select="TitleAdditional" />
            <xsl:apply-templates select="@PublisherName" />
            <xsl:apply-templates select="@PublisherPlace" />
            <xsl:apply-templates select="PersonEditor" />
            <xsl:apply-templates select="PersonTranslator" />
            <xsl:apply-templates select="PersonContributor" />
            <xsl:apply-templates select="PersonOther" />
            <xsl:apply-templates select="PersonReferee" />
            <xsl:apply-templates select="PersonAdvisor" />
            <xsl:apply-templates select="@Type" />
            <xsl:apply-templates select="@Language" />

            <xsl:choose>
                <xsl:when test="string-length(normalize-space(CompletedDate/@Year)) > 0">
                    <xsl:apply-templates select="CompletedDate" />
                </xsl:when>
                <xsl:when test="normalize-space(@CompletedYear) != '0000'">
                    <xsl:apply-templates select="@CompletedYear" />
                </xsl:when>
            </xsl:choose>
            <xsl:choose>
                <xsl:when test="string-length(normalize-space(PublishedDate/@Year)) > 0">
                    <xsl:apply-templates select="PublishedDate" />
                </xsl:when>
                <xsl:when test="normalize-space(@PublishedYear) != '0000'">
                    <xsl:apply-templates select="@PublishedYear" />
                </xsl:when>
            </xsl:choose>

            <xsl:apply-templates select="ThesisPublisher" />
            <xsl:apply-templates select="ThesisGrantor" />
            <xsl:apply-templates select="ThesisDateAccepted" />
            <xsl:apply-templates select="@CreatingCorporation" />
            <xsl:apply-templates select="@ContributingCorporation" />

            <!-- Subjects section:  New subjects must be introduced right here. -->
            <!-- we need to apply a hack (so called Muenchian grouping) here since XSLT's 2.0 for-each-group feature is currently not supported -->
            <xsl:if test="Subject[@Type='uncontrolled']">
            <tr>
                <th class="name">
                    <xsl:call-template name="translateString">
                        <xsl:with-param name="string">subject_frontdoor_uncontrolled</xsl:with-param>
                    </xsl:call-template>
                    <xsl:text>:</xsl:text>
                </th>

                <td><em class="data-marker">
                <xsl:for-each select="Subject[@Type='uncontrolled'][generate-id(.)=generate-id(key('list', @Language))]/@Language">
                    <xsl:sort/>
                    <xsl:for-each select="key('list', .)">
                        <xsl:sort select="@Value" />
                        <xsl:value-of select="@Value"/><xsl:if test="position() != last()">; </xsl:if>
                    </xsl:for-each>
                    <xsl:if test="position() != last()"><br/></xsl:if>
                </xsl:for-each>
                </em></td>
            </tr>
            </xsl:if>
            <xsl:apply-templates select="Subject[@Type='swd']"><xsl:sort select="@Value"/></xsl:apply-templates>
            <xsl:apply-templates select="Subject[@Type='psyndex']"><xsl:sort select="@Value"/></xsl:apply-templates>
            <!-- End Subjects -->
            
            <xsl:apply-templates select="@Volume" />
            <xsl:apply-templates select="@Issue" />
            <xsl:apply-templates select="@Edition" />
            <xsl:apply-templates select="@PageNumber" />
            <xsl:apply-templates select="@PageFirst" />
            <xsl:apply-templates select="@PageLast" />
            <xsl:apply-templates select="Note[@Visibility='public']" />
            
            <!-- Enrichment Section: add the enrichment keys that have to be displayed in frontdoor -->
            <xsl:apply-templates select="Enrichment[@KeyName='Event']" />
            <xsl:apply-templates select="Enrichment[@KeyName='City']" />
            <xsl:apply-templates select="Enrichment[@KeyName='Country']" />
            <!-- Enrichment Fields for Opus3 Documents -->
            <xsl:apply-templates select="Enrichment[@KeyName='SourceTitle']" />
            <xsl:apply-templates select="Enrichment[@KeyName='SourceSwb']" />
            <xsl:apply-templates select="Enrichment[@KeyName='ClassRvk']" />
            <xsl:apply-templates select="Enrichment[@KeyName='ContributorsName']" />
            <xsl:apply-templates select="Enrichment[@KeyName='NeuesSelect']" />
            <!-- End Enrichtments -->
			
            <!-- Collection Roles Section: add the collection roles keys that have to be displayed in frontdoor -->
            <xsl:apply-templates select="Collection[@RoleName='institutes']" />
            <xsl:apply-templates select="Collection[@RoleName='projects']" />

            <xsl:apply-templates select="Collection[@RoleName='ccs']" />
            <xsl:apply-templates select="Collection[@RoleName='ddc']" />
            <xsl:apply-templates select="Collection[@RoleName='msc']" >
                <xsl:sort select="@Number"/>
            </xsl:apply-templates>
            <xsl:apply-templates select="Collection[@RoleName='pacs']" />
            <xsl:apply-templates select="Collection[@RoleName='bk']" />
            <xsl:apply-templates select="Collection[@RoleName='jel']" />
            <xsl:apply-templates select="Collection[@RoleName='series']" />
            <xsl:apply-templates select="IdentifierSerial" />
            
            <xsl:apply-templates select="Collection[@RoleName!='institutes' and @RoleName!='projects' and @RoleName!='ccs' and @RoleName!='ddc' and @RoleName!='msc' and @RoleName!='pacs' and @RoleName!='bk' and @RoleName!='jel' and @RoleName!='series']" />
            <!-- End Collection Roles -->

            <xsl:apply-templates select="Licence" />
        </table>
    </xsl:template>


<!--  -->
<!-- Templates for "internal fields". -->
<!--  -->
    <xsl:template match="@CompletedYear|@ContributingCorporation|@CreatingCorporation|@Volume|@Issue|@Edition">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname" />
            </th>
            <td>
                <xsl:value-of select="." />
            </td>
        </tr>
    </xsl:template>
    
     <xsl:template match="@PageFirst|@PageLast|@PageNumber|@PublishedYear|@PublisherName|@PublisherPlace">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname" />
            </th>
            <td>
                <xsl:value-of select="." />
            </td>
        </tr>
    </xsl:template>   
    
    <xsl:template match="@Language|@Type">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname" />
            </th>
            <td>
                <xsl:call-template name="translateString">
                    <xsl:with-param name="string">
		    <xsl:value-of select="." />
		</xsl:with-param>
                </xsl:call-template>	    
                
            </td>
        </tr>
    </xsl:template>    

<!-- -->
<!-- Templates for "external fields". -->
<!-- -->
    <xsl:template match="Collection">
        <tr>
            <xsl:choose>
                <xsl:when test="position()=1">
                    <th class="name">
                        <xsl:call-template name="translateStringWithDefault">
                            <xsl:with-param name="string">default_collection_role_<xsl:value-of select="@RoleName" /></xsl:with-param>
                            <xsl:with-param name="default"><xsl:value-of select="@RoleName" /></xsl:with-param>
                        </xsl:call-template>
                        <xsl:text>:</xsl:text>
                    </th>
                </xsl:when>
                <xsl:otherwise>
                    <th class="name"></th>
                </xsl:otherwise>
            </xsl:choose>
            <td>
                <xsl:call-template name="checkdisplay"/>
            </td>
        </tr>
    </xsl:template>

    <!-- Catch-all for deleted/invisible collections. -->
    <xsl:template match="Collection[@RoleVisibleFrontdoor='false']">
        <xsl:comment>
            <tr>
                <th class="name">
                    <xsl:value-of select="@Name" />
                    <xsl:text>:</xsl:text>
                </th>
                <td>
                    <xsl:text>(deleted) </xsl:text>
                    <xsl:call-template name="checkdisplay"/>
                </td>
            </tr>
        </xsl:comment>
        <xsl:text>
        </xsl:text>
    </xsl:template>

    <!-- Catch-all for hidden collections. -->
    <xsl:template match="Collection[@Visible='0']">
        <xsl:comment>
            <tr>
                <th class="name">
                    <xsl:value-of select="@Name" />
                    <xsl:text>:</xsl:text>
                </th>
                <td>
                    <xsl:text>(hidden) </xsl:text>
                    <xsl:call-template name="checkdisplay"/>
                </td>
            </tr>
        </xsl:comment>
        <xsl:text>
        </xsl:text>
    </xsl:template>

    <xsl:template match="CompletedDate|PublishedDate|ThesisDateAccepted">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname"/>
            </th>
            <td>
                <xsl:value-of select="concat(format-number(@Day,'00'),'.',format-number(@Month,'00'),'.',@Year)" />
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="Enrichment" mode="unescaped">
        <tr>
            <th class="name">
                <xsl:call-template name="translateString">
                    <xsl:with-param name="string">Enrichment<xsl:value-of select="@KeyName" /></xsl:with-param>
                </xsl:call-template>
             <xsl:text>:</xsl:text>
            </th>
            <td>
                <xsl:value-of select="@Value" disable-output-escaping="yes"/>
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="Enrichment">
        <tr>
            <th class="name">
                <xsl:call-template name="translateString">
                    <xsl:with-param name="string">Enrichment<xsl:value-of select="@KeyName" /></xsl:with-param>
                </xsl:call-template>
	     <xsl:text>:</xsl:text>
            </th>
            <td>
                <xsl:value-of select="@Value" />
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="File[@VisibleInFrontdoor='1']">
        <li>
            <xsl:variable name="fileIcon">
                <img width="16" height="16">
                    <xsl:attribute name="src">
                        <xsl:value-of select="$layoutPath"/>
                        <xsl:text>/img/filetype/</xsl:text>
                        <xsl:call-template name="replaceCharsInString">
                            <xsl:with-param name="stringIn" select="string(@MimeType)"/>
                            <xsl:with-param name="charsIn" select="'/'"/>
                            <xsl:with-param name="charsOut" select="'_'"/>
                        </xsl:call-template>
                        <xsl:text>.png</xsl:text>
                    </xsl:attribute>
                    <xsl:attribute name="alt">
                        <xsl:value-of select="@MimeType"/>
                    </xsl:attribute>
                </img>
            </xsl:variable>

            <xsl:variable name="fileLink">
                <xsl:value-of select="$baseUrl"/>
                <xsl:text>/files/</xsl:text>
                <xsl:value-of select="../@Id" />
                <xsl:text>/</xsl:text>
                <xsl:value-of select="@PathName" />
            </xsl:variable>

            <xsl:variable name="fileLinkText">
                <xsl:choose>
                    <xsl:when test="normalize-space(@Label)">
                        <xsl:value-of select="@Label" />
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:value-of select="@PathName" />
                    </xsl:otherwise>
                </xsl:choose>
                <xsl:if test="@FileSize">
                    <xsl:text> (</xsl:text>
                    <xsl:value-of select="round(@FileSize div 1024)" />
                    <xsl:text> KB)</xsl:text>
                </xsl:if>
            </xsl:variable>

            <xsl:choose>
                <xsl:when test="php:functionString('Frontdoor_IndexController::checkIfUserHasFileAccess', @Id)">
                    <div class="accessible-file">
                        <xsl:attribute name="title">
                            <xsl:call-template name="translateString">
                                <xsl:with-param name="string">frontdoor_download_file</xsl:with-param>
                            </xsl:call-template>
                            <xsl:text> </xsl:text>
                            <xsl:value-of select="@Label" />
                            <xsl:text> (</xsl:text>
                            <xsl:value-of select="@MimeType" />
                            <xsl:text>)</xsl:text>
                        </xsl:attribute>
                        <a>
                            <xsl:attribute name="href">
                                <xsl:copy-of select="$fileLink" />
                            </xsl:attribute>
                            <xsl:copy-of select="$fileIcon" />
                        </a>
                        <xsl:text> </xsl:text>

                        <xsl:element name="a">
                            <xsl:attribute name="href">
                                <xsl:copy-of select="$fileLink" />
                            </xsl:attribute>
                            <xsl:copy-of select="$fileLinkText" />
                        </xsl:element>
                    </div>
                </xsl:when>
                <xsl:otherwise>
                    <div class="protected-file">
                        <xsl:attribute name="title">
                            <xsl:call-template name="translateString">
                                <xsl:with-param name="string">frontdoor_protected_file</xsl:with-param>
                            </xsl:call-template>
                        </xsl:attribute>
                        <xsl:copy-of select="$fileIcon" />
                        <xsl:text> </xsl:text>
                        <xsl:copy-of select="$fileLinkText" />
                    </div>
                </xsl:otherwise>
            </xsl:choose>

            <xsl:if test="@Comment">
                    <xsl:text> </xsl:text>
                    <p>
                        <xsl:value-of select="@Comment" />
                    </p>
            </xsl:if>
        </li>
    </xsl:template>

    <xsl:template match="PersonAuthor|PersonReferee">
        <xsl:if test="position() = 1">
            <xsl:text disable-output-escaping="yes">&lt;tr&gt;</xsl:text>
            <th class="name">
                <xsl:if test="position() = 1">
                   <xsl:call-template name="translateFieldname"/>
                </xsl:if>
            </th>
            <xsl:text disable-output-escaping="yes">&lt;td&gt;</xsl:text>
        </xsl:if>
        <xsl:element name="a">
            <xsl:attribute name="href">
                <xsl:value-of select="$baseUrl"/>
                <xsl:if test="name()='PersonAuthor'"><xsl:text>/solrsearch/index/search/searchtype/authorsearch/author/</xsl:text></xsl:if>
                <xsl:if test="name()='PersonReferee'"><xsl:text>/solrsearch/index/search/searchtype/authorsearch/referee/</xsl:text></xsl:if>
                <xsl:value-of select="concat('&quot;', @FirstName, ' ', @LastName, '&quot;')" />
            </xsl:attribute>
            <xsl:attribute name="title">
                <xsl:if test="name()='PersonAuthor'">
                    <xsl:call-template name="translateString">
                        <xsl:with-param name="string">frontdoor_author_search</xsl:with-param>
                    </xsl:call-template>
                </xsl:if>
                <xsl:if test="name()='PersonReferee'">
                    <xsl:call-template name="translateString">
                        <xsl:with-param name="string">frontdoor_referee_search</xsl:with-param>
                    </xsl:call-template>
                </xsl:if>
            </xsl:attribute>
            <xsl:value-of select="concat(@FirstName, ' ', @LastName)" />
        </xsl:element>
        <xsl:if test="position() != last()">, </xsl:if>
        <xsl:if test="position() = last()">
            <xsl:text disable-output-escaping="yes">&lt;/td&gt;</xsl:text>
            <xsl:text disable-output-escaping="yes">&lt;/tr&gt;</xsl:text>
        </xsl:if>
    </xsl:template>
   

    <xsl:template match="PersonAdvisor|PersonOther|PersonContributor|PersonEditor|PersonTranslator">
        <xsl:if test="position() = 1">
            <xsl:text disable-output-escaping="yes">&lt;tr&gt;</xsl:text>
            <th class="name">
                <xsl:if test="position() = 1">
                   <xsl:call-template name="translateFieldname"/>
                </xsl:if>
            </th>
            <xsl:text disable-output-escaping="yes">&lt;td&gt;</xsl:text>
        </xsl:if>
        <xsl:value-of select="concat(@FirstName, ' ', @LastName)" />
        <xsl:if test="position() != last()">, </xsl:if>
        <xsl:if test="position() = last()">
            <xsl:text disable-output-escaping="yes">&lt;/td&gt;</xsl:text>
            <xsl:text disable-output-escaping="yes">&lt;/tr&gt;</xsl:text>
        </xsl:if>
    </xsl:template>

    <xsl:template match="IdentifierArxiv">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname"/>
            </th>
            <td>
                <xsl:element name="a">
                    <xsl:attribute name="href">
                        <xsl:text>http://arxiv.org/abs/</xsl:text>
                        <xsl:value-of select="@Value" />
                    </xsl:attribute>
                    <xsl:text>http://arxiv.org/abs/</xsl:text>
                    <xsl:value-of select="@Value" />
                </xsl:element>
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="IdentifierPubmed">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname"/>
            </th>
            <td>
                <xsl:element name="a">
                    <xsl:attribute name="href">
                        <xsl:text>http://www.ncbi.nlm.nih.gov/pubmed?term=</xsl:text>
                        <xsl:value-of select="@Value" />
                    </xsl:attribute>
                    <xsl:text>http://www.ncbi.nlm.nih.gov/pubmed?term=</xsl:text>
                    <xsl:value-of select="@Value" />
                </xsl:element>
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="IdentifierHandle|IdentifierUrl">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname"/>
            </th>
            <td>                
                <xsl:element name="a">
                    <xsl:choose>
                        <xsl:when test="starts-with(@Value, 'http://') or starts-with(@Value, 'https://') or starts-with(@Value, 'ftp://')">
                            <xsl:attribute name="href">
                                <xsl:value-of select="@Value" />
                            </xsl:attribute>
                            <xsl:value-of select="@Value" />
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:attribute name="href">
                                <xsl:text>http://</xsl:text><xsl:value-of select="@Value" />
                            </xsl:attribute>
                            <xsl:text>http://</xsl:text><xsl:value-of select="@Value" />
                        </xsl:otherwise>
                    </xsl:choose>
                </xsl:element>
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="IdentifierDoi|ReferenceDoi">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname"/>
            </th>
            <td>
                <xsl:element name="a">
                    <xsl:attribute name="href">
                        <xsl:text>http://dx.doi.org/</xsl:text>
                        <xsl:value-of select="@Value" />
                    </xsl:attribute>
                    <xsl:text>http://dx.doi.org/</xsl:text>
                    <xsl:value-of select="@Value" />
                </xsl:element>
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="IdentifierUrn|ReferenceUrn">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname"/>
            </th>
            <td>
                <xsl:element name="a">                
                    <xsl:attribute name="href">
                        <xsl:text>http://nbn-resolving.de/urn/resolver.pl?</xsl:text>
                        <xsl:value-of select="@Value" />
                    </xsl:attribute>
                    <xsl:value-of select="@Value" />
                </xsl:element>
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="IdentifierIsbn|IdentifierIssn|IdentifierSerial|ReferenceIsbn|ReferenceIssn|ReferenceHandle|TitleParent|TitleSub|TitleAdditional">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname"/>
            </th>
            <td>
                <xsl:value-of select="@Value" />
            </td>
        </tr>
    </xsl:template>
 
    <xsl:template match="Licence">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname"/>
            </th>
            <td>
                <img alt="License Logo">
                    <xsl:attribute name="src">
                        <xsl:value-of select="@LinkLogo"/>
                    </xsl:attribute>
                    <xsl:attribute name="title">
                        <xsl:value-of select="@LinkLicence"/>
                    </xsl:attribute>
                </img>

                <xsl:element name="a">
                    <xsl:attribute name="href">
                        <xsl:value-of select="$baseUrl"/>
                        <xsl:text>/default/license/index/licId/</xsl:text>
                        <xsl:value-of select="@Id"/>
                    </xsl:attribute>
                    <xsl:value-of select="@NameLong"/>
                </xsl:element>
            </td>
        </tr>
    </xsl:template>
      
    <xsl:template match="Note">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname"/>
            </th>
            <td>
                <xsl:value-of select="@Message" />
            </td>
        </tr>
    </xsl:template>
 
    <xsl:template match="Patent"/>
 
    <xsl:template match="ReferenceUrl">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname"/>
            </th>
            <td>
                <xsl:element name="a">
                <xsl:attribute name="href"><xsl:value-of select="@Value" /></xsl:attribute>
                <xsl:attribute name="rel"><xsl:text>nofollow</xsl:text></xsl:attribute>
                <xsl:value-of select="@Label" />
                </xsl:element>
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="Subject">
        <xsl:if test="position() = 1">
            <xsl:text disable-output-escaping="yes">&lt;tr&gt;</xsl:text>
            <xsl:text disable-output-escaping="yes">&lt;th class="name"&gt;</xsl:text>
            <xsl:call-template name="translateString">
                <xsl:with-param name="string">subject_frontdoor_<xsl:value-of select="@Type" /></xsl:with-param>
            </xsl:call-template>
            <xsl:text>:</xsl:text>
            <xsl:text disable-output-escaping="yes">&lt;/th&gt;</xsl:text>
            <xsl:text disable-output-escaping="yes">&lt;td&gt;&lt;em class="data-marker"&gt;</xsl:text>
        </xsl:if>
        <xsl:value-of select="@Value" /><xsl:if test="position() != last()">; </xsl:if>
        <xsl:if test="position() = last()">
            <xsl:text disable-output-escaping="yes">&lt;/em&gt;&lt;/td&gt;</xsl:text>
            <xsl:text disable-output-escaping="yes">&lt;/tr&gt;</xsl:text>
        </xsl:if>
    </xsl:template>


    <xsl:template match="ThesisGrantor|ThesisPublisher">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname"/>
            </th>
            <td>
                <xsl:value-of select="@Name" />
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="IdentifierStdDoi"/>
    <xsl:template match="IdentifierCrisLink"/>
    <xsl:template match="IdentifierSplashUrl"/>
    <xsl:template match="ReferenceStdDoi"/>
    <xsl:template match="ReferenceCrisLink"/>
    <xsl:template match="ReferenceSplashUrl"/>


<!-- -->
<!-- Named Templates for the introducing block (Author, Title, Abstract). -->
<!-- -->
    <xsl:template name="Author">
        <p>
            <xsl:for-each select="PersonAuthor">
                <xsl:element name="a">
                    <xsl:attribute name="href">
                        <xsl:value-of select="$baseUrl"/>
                        <xsl:text>/solrsearch/index/search/searchtype/authorsearch/author/</xsl:text>
                        <xsl:value-of select="concat('&quot;', @FirstName, ' ', @LastName, '&quot;')" />
                    </xsl:attribute>
                    <xsl:attribute name="title">
                        <xsl:call-template name="translateString">
                            <xsl:with-param name="string">frontdoor_author_search</xsl:with-param>
                        </xsl:call-template>
                    </xsl:attribute>
                    <xsl:value-of select="concat(@FirstName, ' ', @LastName)" />
                </xsl:element>
                <xsl:if test="not(position()=last())">
                    <xsl:text>, </xsl:text>
                </xsl:if>
            </xsl:for-each>
        </p>
    </xsl:template>

    <xsl:template name="Title">
        <p>
            <xsl:for-each select="TitleMain">
                <xsl:if test="position() = 1">
                    <h2 class="titlemain">
                        <xsl:value-of select="@Value" />
                    </h2>
                </xsl:if>
                <xsl:if test="position() > 1">
                    <h3 class="titlemain">
                        <xsl:value-of select="@Value" />
                    </h3>
                </xsl:if>
            </xsl:for-each>
        </p>
    </xsl:template>

    <xsl:template name="Abstract">
        <p>
            <xsl:for-each select="TitleAbstract">
                <xsl:if test="position() = 1">
                    <div class="abstract">
                        <xsl:value-of select="@Value" />
                    </div>
                </xsl:if>
                <xsl:if test="position() > 1">
                    <div class="abstract">
                        <xsl:value-of select="@Value" />
                    </div>
                </xsl:if>
            </xsl:for-each>
        </p>
    </xsl:template>


<!-- -->
<!-- Named Templates for the service block (MailToAuthor, AdditionalServices, ExportFunctions). -->
<!-- -->
    <xsl:template name="MailToAuthor">
        <xsl:if test ="$isMailPossible">
            <xsl:element name="br"/>
            <xsl:element name="a">
                <!-- TODO: Use Zend Url-Helper to build href attribute -->
                <xsl:attribute name="href">
                    <xsl:value-of select="$baseUrl"/>
                    <xsl:text>/frontdoor/mail/toauthor/docId/</xsl:text>
                    <xsl:value-of select="@Id" />
                </xsl:attribute>
                <xsl:call-template name="translateString">
                    <xsl:with-param name="string">frontdoor_mailtoauthor</xsl:with-param>
                </xsl:call-template>
            </xsl:element>
        </xsl:if>
    </xsl:template>

    <!--  Named template for services-buttons -->
    <xsl:template name="AdditionalServices">
        <!-- Twitter -->
        <xsl:variable name="frontdoor_share_twitter">
            <xsl:call-template name="translateString">
                <xsl:with-param name="string">frontdoor_share_twitter</xsl:with-param>
            </xsl:call-template>
        </xsl:variable>

        <a>
            <xsl:attribute name="href">
                <xsl:text disable-output-escaping="yes">http://twitter.com/share?url=</xsl:text>
                <xsl:value-of select="$baseUrlServer"/>
                <xsl:text>/frontdoor/index/index/docId/</xsl:text>
                <xsl:value-of select="@Id" />
            </xsl:attribute>
            <img>
                <xsl:attribute name="src">
                    <xsl:value-of select="$layoutPath"/>
                    <xsl:text>/img/twitter.png</xsl:text>
                </xsl:attribute>
                <xsl:attribute name="name">
                    <xsl:value-of select="$frontdoor_share_twitter"/>
                </xsl:attribute>
                <xsl:attribute name="title">
                    <xsl:value-of select="$frontdoor_share_twitter"/>
                </xsl:attribute>
                <xsl:attribute name="alt">
                    <xsl:value-of select="$frontdoor_share_twitter"/>
                </xsl:attribute>
            </img>
        </a>
        <xsl:text> </xsl:text>

        <!-- google-scholar -->
        <xsl:if test="normalize-space(TitleMain/@Value)">
            <xsl:element name="a">
           <!-- TODO: Use Zend Url-Helper to build href attribute -->
                <xsl:attribute name="href">
                    <xsl:text disable-output-escaping="yes">http://scholar.google.de/scholar?hl=de&amp;q="</xsl:text>
                    <xsl:value-of select="TitleMain/@Value"/>
                    <xsl:text>"</xsl:text>
                </xsl:attribute>
                <xsl:element name="img">
                    <xsl:attribute name="src">
                        <xsl:value-of select="$layoutPath"/>
                        <xsl:text>/img/google_scholar.jpg</xsl:text>
                    </xsl:attribute>
                    <xsl:attribute name="title">
                        <xsl:call-template name="translateString">
                            <xsl:with-param name="string">frontdoor_searchgoogle</xsl:with-param>
                        </xsl:call-template>
                    </xsl:attribute>
                    <xsl:attribute name="alt">
                        <xsl:call-template name="translateString">
                            <xsl:with-param name="string">frontdoor_searchgoogle</xsl:with-param>
                        </xsl:call-template>
                    </xsl:attribute>
                </xsl:element>
            </xsl:element>
            <xsl:text> </xsl:text>
        </xsl:if>
     </xsl:template>

    <xsl:template name="ExportFunctions">
        <!-- Bib-Export -->
        <li>
            <xsl:element name="a">
               <!-- TODO: Use Zend Url-Helper to build href attribute -->
                <xsl:attribute name="href">
                    <xsl:value-of select="$baseUrl"/>
                    <xsl:text>/citationExport/index/download/output/bibtex/docId/</xsl:text>
                    <xsl:value-of select="@Id" />
                </xsl:attribute>
                <xsl:element name="img">
                    <xsl:attribute name="src">
                        <xsl:value-of select="$layoutPath"/>
                        <xsl:text>/img/bibtex_w.png</xsl:text>
                    </xsl:attribute>
                    <xsl:attribute name="name">
                        <xsl:text>bibtex</xsl:text>
                    </xsl:attribute>
                    <xsl:attribute name="onmouseover">
                        <xsl:text>document.bibtex.src='</xsl:text>
                        <xsl:value-of select="$layoutPath"/>
                        <xsl:text>/img/bibtex_o.png';</xsl:text>
                    </xsl:attribute>
                    <xsl:attribute name="onmouseout">
                        <xsl:text>document.bibtex.src='</xsl:text>
                        <xsl:value-of select="$layoutPath"/>
                        <xsl:text>/img/bibtex_w.png';</xsl:text>
                    </xsl:attribute>
                    <xsl:attribute name="title">
                        <xsl:call-template name="translateString">
                            <xsl:with-param name="string">frontdoor_exportbibtex</xsl:with-param>
                        </xsl:call-template>
                    </xsl:attribute>
                    <xsl:attribute name="alt">
                        <xsl:call-template name="translateString">
                            <xsl:with-param name="string">frontdoor_exportbibtex</xsl:with-param>
                        </xsl:call-template>
                    </xsl:attribute>
                </xsl:element>
            </xsl:element>
        </li>
        <xsl:text> </xsl:text>

        <!-- Ris-Export -->
        <li>
            <xsl:element name="a">
               <!-- TODO: Use Zend Url-Helper to build href attribute -->
                <xsl:attribute name="href">
                    <xsl:value-of select="$baseUrl"/>
                    <xsl:text>/citationExport/index/download/output/ris/docId/</xsl:text>
                    <xsl:value-of select="@Id" />
                </xsl:attribute>
                <xsl:element name="img">
                    <xsl:attribute name="src">
                        <xsl:value-of select="$layoutPath"/>
                        <xsl:text>/img/ris_w.png</xsl:text>
                    </xsl:attribute>
                    <xsl:attribute name="name">
                        <xsl:text>ris</xsl:text>
                    </xsl:attribute>
                    <xsl:attribute name="onmouseover">
                        <xsl:text>document.ris.src='</xsl:text>
                        <xsl:value-of select="$layoutPath"/>
                        <xsl:text>/img/ris_o.png';</xsl:text>
                    </xsl:attribute>
                    <xsl:attribute name="onmouseout">
                        <xsl:text>document.ris.src='</xsl:text>
                        <xsl:value-of select="$layoutPath"/>
                        <xsl:text>/img/ris_w.png';</xsl:text>
                    </xsl:attribute>
                    <xsl:attribute name="title">
                        <xsl:call-template name="translateString">
                            <xsl:with-param name="string">frontdoor_exportris</xsl:with-param>
                        </xsl:call-template>
                    </xsl:attribute>
                    <xsl:attribute name="alt">
                        <xsl:call-template name="translateString">
                            <xsl:with-param name="string">frontdoor_exportris</xsl:with-param>
                        </xsl:call-template>
                    </xsl:attribute>
                </xsl:element>
            </xsl:element>
        </li>
    </xsl:template>
    
<!-- -->
<!-- Additional Templates with auxilliary functions. -->
<!-- -->
    <!-- Named template to proof, what to show for collections, depending on display_frontdoor -->
    <xsl:template name="checkdisplay">
        <xsl:if test="contains(@RoleDisplayFrontdoor,'Number') and @Number != ''">
            <xsl:value-of select="@Number" />
            <xsl:text> </xsl:text>
        </xsl:if>
        <xsl:if test="contains(@RoleDisplayFrontdoor,'Name') and @Name != ''">
            <xsl:value-of select="@Name" />
        </xsl:if>
    </xsl:template>

    <!-- Named template to translate a field's name. Needs no parameter. -->
    <xsl:template name="translateFieldname">
        <xsl:value-of select="php:functionString('Frontdoor_IndexController::translate', name())" />
        <xsl:if test="normalize-space(@Language)">
            <!-- translation of language abbreviations  -->
            <xsl:text> (</xsl:text>
            <xsl:call-template name="translateString">
                <xsl:with-param name="string" select="@Language" />
            </xsl:call-template>
            <xsl:text>)</xsl:text>
        </xsl:if>
	<xsl:text>:</xsl:text>
    </xsl:template>

    <!-- Named template to translate an arbitrary string. Needs the translation key as a parameter. -->
    <xsl:template name="translateString">
        <xsl:param name="string" />
        <xsl:value-of select="php:functionString('Frontdoor_IndexController::translate', $string)" />
    </xsl:template>

    <xsl:template name="translateStringWithDefault">
        <xsl:param name="string" />
        <xsl:param name="default" />
        <xsl:value-of select="php:functionString('Frontdoor_IndexController::translateWithDefault', $string, $default)" />
    </xsl:template>

    <xsl:template name="replaceCharsInString">
        <xsl:param name="stringIn"/>
        <xsl:param name="charsIn"/>
        <xsl:param name="charsOut"/>
        <xsl:choose>
            <xsl:when test="contains($stringIn,$charsIn)">
                <xsl:value-of select="concat(substring-before($stringIn,$charsIn),$charsOut)"/>
                <xsl:call-template name="replaceCharsInString">
                    <xsl:with-param name="stringIn" select="substring-after($stringIn,$charsIn)"/>
                    <xsl:with-param name="charsIn" select="$charsIn"/>
                    <xsl:with-param name="charsOut" select="$charsOut"/>
                </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="$stringIn"/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    
</xsl:stylesheet>
