#include <cstdlib>
#include <cstring>
#include <iostream>
#include <fstream>
#include <string>
#include <sstream>
#include <vector>
#include <map>
#include <unordered_map>
#include <ctime>
#include <fcntl.h>
#include <sys/types.h>
#include <unistd.h>
using namespace std;

/*
argv
1. mainFile
2. prefixFile
3. resultFile
*/
int main(int argc, char *argv[])
{
  string path = argv[1],
         prefixFile = argv[2];

  ofstream finishFile, currentOutput;
  ifstream hAllRows, hViews, hListOfAggFiles;
  unordered_map<string,int> listAllData;
  unordered_map<string,int>::iterator it;

  string pr, pr2, pr3, name;
  time_t t0, t1, t2;
  time(&t1);
  t0 = t1;
  int views, pos, i = 0;

  int work = 0;
  string currentLang = "";
  hAllRows.open(path.c_str(), ios::in | ios::binary);
  while (!hAllRows.eof()) {
    getline(hAllRows, pr);
    istringstream iss(pr);
    getline(iss, pr, ' ');
    istringstream iss2(pr);
    getline(iss2, pr, '.');

    if (pr != currentLang) {
      currentLang = pr;
     
      if (work != 0) {
        currentOutput.close();
      }

      currentOutput.open((prefixFile + currentLang).c_str(), ios::app | ios::binary);
      work = 1;
    }

    getline(iss, pr);
    currentOutput << pr << "\n";
  }
  hAllRows.close();
    if (work != 0) {
        currentOutput.close();
      }
  time(&t2);
  cout<<"finish"<<t2 - t0<<"\n";
  cout<<"SUCCESS\n";

  return EXIT_SUCCESS;
}

