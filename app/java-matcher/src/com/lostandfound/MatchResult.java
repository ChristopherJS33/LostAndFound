package com.lostandfound;

public class MatchResult {
    private final int id;
    private final double score;

    public MatchResult(int id, double score) {
        this.id = id;
        this.score = score;
    }

    public int getId() { return id; }
    public double getScore() { return score; }
}
