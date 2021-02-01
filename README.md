# mytrics
This is a program that generates a bibliometric report for an author using the Scopus API (you'll need to add your own API key). Information is retrieved via ORCID or the Scopus author ID.

The output includes the h-factor and a variant I called the H5, which is the h-factor for papers published within the last 5 years. The H5 seems potentially useful as a window on more recent work, as opposed to the standard h-factor that can only increase over time. This might allow more relevant comparisons / better incentives in certain contexts, although all use of such metrics requires careful thought and caution to make sure they're used for good (but of course - so does not using metrics, and metrics do seem to me to inherently let themselves be relatively transparently analyzed and criticized). Generally, I'd say that it's clear that h-factors and similar metrics can be gamed, so in the first instance I'd say they're mainly an upper bound of influence; but used honestly and fairly I think they can be helpful, e.g., in identifying where work may not be being picked up.

The program also makes a crude prediction of the development of the h-factor over the next years.

https://www.tegladwin.com/Misc/2021_01_27_Scopus/mytrics.php

[![DOI](https://zenodo.org/badge/335036249.svg)](https://zenodo.org/badge/latestdoi/335036249)

