package com.lostandfound;

import java.util.ArrayList;
import java.util.Comparator;
import java.util.List;

public class MatchService {
    private final Matcher matcher;

    public MatchService(Matcher matcher) {
        this.matcher = matcher;
    }

    public List<MatchResult> findMatches(Item sourceItem, List<? extends Item> candidateItems) {
        List<MatchResult> results = new ArrayList<>();

        for (Item candidate : candidateItems) {
            if (candidate.getId() == sourceItem.getId()) continue;
            double score = matcher.calculateMatchScore(sourceItem, candidate);
            if (score >= 0.30) {
                results.add(new MatchResult(candidate.getId(), score));
            }
        }

        results.sort(Comparator.comparingDouble(MatchResult::getScore).reversed());
        return results;
    }
}
