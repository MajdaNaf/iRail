iRail package for The DataTank - v2
===================================

Beta
----
This feature is very much beta and all code reviews and pull requests are welcome!

Reason for a new api
--------------------
To be independant from the max queries on the data providers website we now provide api calls to a stored dataset.


About the code
--------------

The modeldrivers are fully swappable. If another resource is stored in a gtfs, just write a gtfs model driver, add it to the array and write: $stopmodel->gtfs->get(...);

... More to come. Please wake me when this documentation is not completed before january 2013.