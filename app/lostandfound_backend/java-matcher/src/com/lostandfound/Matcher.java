package com.lostandfound;

public interface Matcher {
    double calculateMatchScore(Item source, Item candidate);
}
